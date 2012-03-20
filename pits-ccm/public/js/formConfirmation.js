/**
 * Application PiTS CCM
 * Fonctions permettant de griser ou dégriser le bouton 'Confirmation' phase 3 de l'inscription des enfants par les parents
 * View concernée : application/views/scripts/parent/inscrire
 * 
 * Date de création: 13 mars 2012
 * Date révision:
 * 
 */

/**
 * Dégrise le bouton Confirmer (envoir) si les deux checkbox sont cochées
 */
function etatconfirmer() {
	var checkbox1 = document.getElementById('tousinscrits');
	var checkbox2 = document.getElementById('justificatifs');
	var button = document.getElementById('envoi');
	
	//alert(checkbox1.checked && checkbox2.checked ? "actif" : "grisé");
	if (checkbox1.checked && checkbox2.checked) {
		button.disabled = false;
		//alert('actif');
	} else {
		button.disabled = true;
		//alert('grisé');
	}
}

/**
 * Dégrise le bouton suite et appelle le pdf des justificatifs
 * @param string url
 */
function voirsuite(url) {
	var suite2 = document.getElementById('suite2');
	suite2.disabled = false;
	window.location.href=url;
	return false;
}