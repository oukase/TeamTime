// index.js
//

$(function() {
	$('#nav2-text h1').toggle(function() {
		$('#fConn').css('display', 'block');
		$('#fConn').addClass('largeConnexion');
		}
		,function() {
		$('#fConn').css('display', 'none');
		$('#fConn').removeClass('largeConnexion');
		});
	$('#nav4-text h1').toggle(function() {
		$('#fSignup').css('display', 'block');
		}
		,function() {
		$('#fSignup').css('display', 'none');
		});
});
