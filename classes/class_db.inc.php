<?php
// class_db.inc.php

// Librairie de fonctions pour mysql

/*
	TeamTime is a software to manage people working in team on a cyclic shift.
	Copyright (C) 2012 Manioul - webmaster@teamtime.me

	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU Affero General Public License as
	published by the Free Software Foundation, either version 3 of the
	License, or (at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU Affero General Public License for more details.

	You should have received a copy of the GNU Affero General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

set_include_path(implode(PATH_SEPARATOR, array(realpath('.'), get_include_path())));

require_once 'constantes.inc.php';

class database {
	private $link;
	private $DSN;
// Constructeur
	function __construct($dsn = NULL) {
		if (isset($dsn)) {
			if (is_array($dsn)) {
				$this->DSN = $dsn;
			}
		} else {
			$this->DSN = $GLOBALS['DSN']['nobody'];
		}
		if (FALSE === $this->_db_connect()) {
			return FALSE;
		}
		if ( !mysqli_set_charset($this->link, $this->DSN['NAMES']) ) {
			// Erreur du passage en utf8 du dialogue avec la base de donn�es
			firePHPInfo(sprintf('Erreur du passage en %s du dialogue avec la base.', $this->DSN['NAMES']));
			return FALSE;
		} else {
			firePHPInfo(sprintf('Passage en %s du dialogue avec la base.', $this->character_set()));
		}
		return TRUE;
	}
	function __destruct() {
		$this->_db_ferme();
	}
	function __sleep() {
		return array( 'DSN' );
	}
	function __wakeup() {
		$this->_db_connect();
	}
// Accesseurs
	// Renvoie l'encoding du dialogue avec la bdd pour �tre utiliser par htmlspecialchars
	public function encoding() {
		// Tableau des �quivalences entre les noms d'encodage pour la base de donn�es
		// (tels que saisis dans le DSN) et les noms d'encodages compris par htmlspecialchars
		$encodings = array(
			'utf8'		=> "UTF-8"
		);
		return $encodings[$this->DSN['NAMES']];
	}
	public function character_set() {
		return mysqli_character_set_name($this->link);
	}
// Gestion de la connexion
	private function _uns_db_connect() {
		$this->link = @mysqli_connect( $this->DSN['hostname'], $this->DSN['username'], $this->DSN['password'], $this->DSN['dbname'] );
		if (mysqli_connect_errno())
		{ // Erreur de connexion
			debug::getInstance()->lastError(ERR_DB_CONN);
			$this->link = ERR_DB_CONN;
		}
	}
	private function _db_connect() {
		if (! is_array($this->DSN)) { $this->DSN = $GLOBALS['DSN']['nobody']; }
		if ( ERR_DB_CONN === $this->_uns_db_connect() ) {
			print ( 'Erreur de connexion (' . mysqli_connect_errno() . ') ' . mysqli_connect_error() );
			return FALSE;
		}
		$this->_uns_db_set_NAMES();
	}

	private function _uns_db_ferme() {
		if (is_resource($this->link)) {
			mysqli_close($this->link);
		} else {
			debug::getInstance()->lastError(ERR_RSRC_EXPECTED);
		}
	}
	private function _db_ferme() {
		$this->_uns_db_ferme();
	}
	private function _uns_db_set_NAMES() {
		if (!empty($this->DSN['NAMES'])) {
			mysqli_set_charset($this->link, $this->DSN['NAMES']);
			//$this->db_interroge( sprintf("SET NAMES '%s'", $this->DSN['NAMES']) ); // Plante la bdd avec mysqli
		}
	}
	public function change_user($DSN) {
		if (mysqli_change_user($this->link, $DSN['username'], $DSN['password'], $DSN['dbname'])) {
			$this->DSN = $DSN;
			return true;
		}
		return false;
	}
// Interrogation
	public function db_interroge ($query) {
		if (! (is_resource($this->link)) ) {
			$this->_db_connect();
		}
		if ( ( $result = mysqli_query ( $this->link, $query ) ) === FALSE)
		{ // Erreur de requ�te
			$result = "Erreur";
			debug::getInstance()->lastError(ERR_DB_SQL);
			debug::getInstance()->triggerError(sprintf("Erreur de requ�te (%s): %s\n", $query, mysqli_error($this->link)));
			//printf("Erreur de requ�te (%s): %s\n", $query, mysqli_error($this->link));
		}
		return $result;
	}
	public function db_insert_id() {
		return mysqli_insert_id($this->link);
	}
	public function db_affected_rows() {
		return mysqli_affected_rows($this->link);
	}
	public function db_fetch_row($result) {
		return mysqli_fetch_row($result);
	}
	public function db_fetch_assoc($result) {
		return mysqli_fetch_assoc($result);
	}
	public function db_fetch_array($result) {
		return mysqli_fetch_array($result);
	}
	// Cr�ation d'une requ�te � partir d'un tableau pass� en argument
	public function db_requeteFromArray($array) {
		$requete = '';
		foreach ($array as $query) {
			$requete .= $query . ";\n";
		}
		print("$requete");
		return $requete;
	}
	public function db_interrogeArray($array) {
		foreach ($array as $query) {
			$this->db_interroge($query);
		}
	}
	// Cr�ation d'une requ�te employant une transcation
	public function db_transactionArray($array) {
		return "SET AUTOCOMMIT = 0; START TRANSACTION;" . $this->db_requeteFromArray($array) . "COMMIT;SET AUTOCOMMIT = 1;";
	}
	// Interrogation de la bdd en utilisant une transaction
	public function db_interrogeTransactionArray($array) {
		print $this->db_transactionArray($array);
		$this->db_interroge($this->db_transactionArray($array));
	}
	// Retourne un tableau contenant les caract�ristiques d'une table dont le nom est pass� en argument
	//           champ   type  null  key  default  extra
	// colonne 1  ..      ..    ..    ..     ..     ..
	// colonne 2  ..      ..    ..    ..     ..     ..
	// ...
	public function db_getColumnsTable($table) {
		$result = $this->db_interroge("SHOW COLUMNS FROM `$table`");
		$fields = array();
		while ($row = mysqli_fetch_assoc($result)) {
			if ($row['Null'] == "YES") {
				$row['Null'] = "NULL";
			} else {
				$row['Null'] = "NOT NULL";
			}
			$fields[$row['Field']] = $row;
		}
		mysqli_free_result($result);
		//print (nl2br(print_r ($fields, TRUE)));
		return $fields;
	}
	// Retourne les champs d'une table
	// sous forme de tableau si le deuxi�me param�tre est 'array'
	// sinon sous forme de cha�ne pour pr�parer une insertion si le deuxi�me param�tre est 'insert'
	public function db_getFields($table, $param = 'array') {
		$result = $this->db_interroge("SHOW COLUMNS FROM `$table`");
		if ($param == 'array') {
			$return = array();
			while ($row = $this->db_fetch_assoc($result)) {
				$return[] = $row['Field'];
			}
		} elseif ($param == 'insert') {
			$return = "";
			while ($row = $this->db_fetch_assoc($result)) {
				$return .= "`" . $row['Field'] . "`, ";
			}
			$return = substr($return, 0, -2);
		}
		mysqli_free_result($result);
		return $fields;
	}
	// Effectue une requ�te d'insertion des champs $values dans la table $table
	// et retourne la valeur de l'id ins�r�
	// $values = array('Field' => "value"...)
	public function db_insert($table, $values) {
		$sql = "INSERT INTO `$table` (";
		$val = " VALUES (";
		foreach ($this->db_getColumnsTable($table) as $row) {
			$sql .= "`" . $row['Field'] . "`, ";
			if ($row['Key'] == 'PRI') {
				$val .= "NULL, ";
			} else {
				$val .= "'" . $this->db_real_escape_string($values[$row['Field']]) . "', ";
			}
		}
		$sql = substr($sql, 0, -2) . ")";
		$val = substr($val, 0, -2) . ")";
		firePhpLog($sql . $val, 'insert request from class db');
		$this->db_interroge($sql . $val);
		return $this->db_insert_id();
	}
	// Effectue une requ�te UPDATE de la table $table avec les valeurs $values
	// la r�f�rence est la cl� primaire
	public function db_update($table, $values) {
		$sql = "UPDATE `$table` SET ";
		$where = " WHERE ";
		$flag = false;
		foreach ($this->db_getColumnsTable($table) as $row) {
			if (!isset($values[$row['Field']])) continue;
			if ($row['Key'] == 'PRI') {
				if ($flag) $where .= " AND ";
				$where .= "`" . $row['Field'] . "` = '" . $this->db_real_escape_string($values[$row['Field']]) . "'";
			} else {
				$sql .= "`" . $row['Field'] . "` = '" . $this->db_real_escape_string($values[$row['Field']]) . "', ";
			}
		}
		$sql = substr($sql, 0, -2);
		firePhpLog($sql . $where, 'update request from class db');
		return $this->db_interroge($sql . $where);
	}
	// Retourne un tableau exploitable pour cr�er un formulaire
	// � partir des caract�ristiques d'une table dont le nom est pass� en param�tre
	// $correspondances est un tableau contenant des correspondances
	// entre les champs de la table et une �tiquette � afficher dans le tableau html
	public function db_columnToForm($table, $correspondance = array()) {
		$fields = $this->db_getColumnsTable($table);
		$fieldtype = array( 0 => 
			array('name'	=> 'boolean'
			,'pattern'	=> '/^tinyint\(1\)$/i'
			,'formtype'	=> 'checkbox'
			,'value'	=> 1
			)
			,1 =>
			array('name'	=> 'integer'
			,'pattern'	=> '/^(tiny|small|medium|big)*int(\(([^1]|[1-9][0-9][0-9]*)\))$/i'
			,'formtype'	=> 'text')
			,2 =>
			array('name'	=> 'text'
			,'pattern'	=> '/^((var)*char|(tiny|medium|long)*text)\(([0-9]+)\)$/i'
			,'formtype'	=> 'text')
			,3 =>
			array('name'	=> 'date'
			,'pattern'	=> '/date/i'
			,'formtype'	=> 'date')
			,4 =>
			array('name'	=> 'liste'
			,'pattern'	=> "/^enum(\(.+\))$/i"
			,'formtype'	=> 'select')
			,5 =>
			array('name'	=> 'multiple'
			,'pattern'	=> "/^set(\(.+\))$/i"
			,'formtype'	=> 'select')
		);
		foreach ($fields as $Field => $row) {
			// D�tection du type d'�l�ment INPUT � attribuer
			foreach ($fieldtype as $ft) {
				if (preg_match($ft['pattern'], $fields[$Field]['Type'], $matches)) {
					$fields[$Field]['Input'] = $ft['formtype'];
					if (isset($correspondances[$Field])) {
						$fields[$Field]['label'] = $correspondances[$Field];
					} else {
						$fields[$Field]['label'] = $Field;
					}
					if (isset($ft['value'])) $fields[$Field]['value'] = $ft['value'];
					if ($ft['formtype'] === 'text' && isset($matches[1])) {
						$fields[$Field]['maxlength'] = $matches[1];
						$fields[$Field]['width'] = $matches[1];
					} elseif ($ft['formtype'] === 'select') {
						if (preg_match_all("/'([^()']+)'/Ui", $matches[1], $moui)) {
							$fields[$Field]['Select'] = $moui[1];
						}
					}
					break;
				}
			}
		}
		return $fields;
	}
	// Protection de cha�nes
	// Retourne une cha�ne prot�g�e qui peut �tre int�gr�e dans une requ�te mysql
	public function db_real_escape_string($string) {
		return mysqli_real_escape_string($this->link, $string);
	}
	// Copie la structure d'une table $origin
	// vers la table $dest
	// si $drop est non null la table $dest est supprim�e avant
	// si $data est non null les donn�es sont recopi�es
	public function db_copy_table($origin, $dest, $drop = NULL, $datas = NULL) {
		if ($origin != $this->db_real_escape_string($origin) || $dest != $this->db_real_escape_string($dest)) {
			return FALSE;
		}
		$copy = "";
		if (!is_null($drop)) {
			$this->db_interroge("DROP TABLE IF EXISTS `$dest`");
		}
		$row = $this->db_fetch_row($this->db_interroge("SHOW CREATE TABLE `$origin`"));
		$copy .= preg_replace("/$origin/", $dest, $row[1]);
		$this->db_interroge($copy);
		if (!is_null($datas)) {
			$this->db_interroge("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO'");
			$this->db_interroge("INSERT INTO `$dest`
				SELECT *
				FROM `$origin`");
		}
	}
	// Retourne dans un tableau les valeurs SET ou ENUM d'un champ
	// Le premier param�tre est la table, le second est le champ
	public function db_set_enum_to_array($table, $field) {
		$aEnum = array();
		$sql = sprintf("
			SHOW COLUMNS
			FROM `%s`
			LIKE '%s'
			", $this->db_real_escape_string($table)
			, $this->db_real_escape_string($field)
		);
		$row = $_SESSION['db']->db_fetch_assoc($_SESSION['db']->db_interroge($sql));
		preg_match('/^(set|enum)\((.*)\)$/', $row['Type'], $enum);
		$aEnum = preg_split("/[,'+]/", $enum[2], NULL, PREG_SPLIT_NO_EMPTY);
		$aEnum['Type'] = $row[1]; // Le type SET ou ENUM
		return $aEnum;
	}
}

?>
