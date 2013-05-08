{* Smarty *}
{* ATTENTION à ne pas ajouter de retour charriot qui casse le code javascript notamment du décompte de présents >:o *}
{* Huh ?! Malgré des modifications de mise en page dans ce template, le code semble continuer de fonctionner normalement *}
<div id="tgrille">
	<table id="grille">
{foreach from=$grille key="lineNb" item="lines" name="lineBoucle"}{* Les deux premières lignes sont dans un thead, les suivantes dans le tbody *}
	{if $lineNb == 0}<thead>{elseif $lineNb == 2}</thead>
	<tbody>{/if}
	<tr id="line{$lineNb}">
{foreach from=$lines item=value}
	{if $lineNb < 2}<th{else}<td{/if}{if $value.id} id="{$value.id}"{/if}{if $value.classe} class="{$value.classe}"{/if}{if $value.title} title="{$value.title|escape:html}"{/if}{if $value.colspan} colspan="{$value.colspan}"{/if}>{if $value.navigateur}{* Construction d'un navigateur entre les cycles pour la case contenant l'année *}
	<div class="nav-prev"><a href="?dateDebut={$previousCycle}" title="Reculer d'un cycle"><img src="{$image}" class="nav-prev" alt="&lt;" /></a></div>
	<div class="nav-present"><a href="?dateDebut={$presentCycle}">{$value.nom}</a></div>
	<div class="nav-next"><a href="?dateDebut={$nextCycle}" title="Avancer d'un cycle"><img src="{$image}" class="nav-next" alt="&lt;" /></a></div>{elseif $value.vacation}
	<div class="{$value.vacances}"></div>
	<div class="{$value.periodeCharge}"></div>
	<div class="{if !$value.briefing}no{/if}brief"{if $value.briefing} title="{$value.briefing}"{/if}></div>
	<div class="dateGrille">{$value.jds}</div>
	<div class="dateGrille">{$value.jdm}</div>
	<div class="shift">{$value.vacation}</div>{else}{$value.nom}{/if}
{if $lineNb < 2}
	</th>{else}</td>
	{/if}{/foreach}</tr>
{if $smarty.foreach.lineBoucle.last}</tbody>{/if}{/foreach}
	</table>
</div>
{*
	div qui contiendra la liste de valeurs à attribuer à une case de la grille
		Le contenu est construit dynamiquement, en fonction de la case à modifier
		*} 
		<div id="sandbox"></div>
{*	div qui contient un formulaire pour les remplacement *}
		<form id="fFormRemplacement" method="post" action="set_rempla.php">
		<div id="dFormRemplacement">
				<input type="hidden" name="uid" id="remplaUid" />
				<input type="hidden" name="Year" id="remplaYear" />
				<input type="hidden" name="Month" id="remplaMonth" />
				<input type="hidden" name="Day" id="remplaDay" />
			<table>
			<tr>
			<td>
				<label for="remplaNom">Nom&nbsp;:</label>
			</td><td>
				<input name="nom" id="remplaNom" type="text" />
			</td></tr><tr><td>
				<label for="remplaPhone">Téléphone&nbsp;:</label>
			</td><td>
				<input name="phone" id="remplaPhone" type="text" />
			</td></tr><tr><td>
				<label for="remplaEmail">remplaEmail&nbsp;</label>
			</td><td>
				<input name="email" id="remplaEmail" type="text" />
			</td></tr><tr><td>
				<label for="remplaAlert">Envoyer un mail&nbsp;:</label>
			</td><td>
				<input name="alert" id="remplaAlert" type="checkbox" />
			</td></tr><tr><td>
				<input type="reset" value="Effacer" />
			</td><td>
				<input type="submit" />
			</td></tr>
			</table>
		</div>
		</form>
{* Des messages de debug peuvent être passés dans ce div
	*}
	{* <div id="debugMessages"><a href="#">Hide</a></div> *}
