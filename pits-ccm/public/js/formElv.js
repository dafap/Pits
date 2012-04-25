/**
 * Application PiTS Formulaire de saisie d'un élève pour
 * PiTS/Form/InscriptionEleve
 * 
 * Date de création: 14 mai 2010
 * Date révision: 06/06/2011
 */

/**
 * Retourne la valeur du bouton radio sélectionné
 * 
 * @param nomElement
 *            (nom du bouton radio)
 * @param nbChoix
 *            (nombre de choix dans le bouton radio)
 * @return valeur du choix sélectionné
 * @since 06/06/2011
 */
function valeurRadio(nomElement, nbChoix) {
	var radio = document.getElementsByName(nomElement);
	var res;
	for ( var i = 0; i < nbChoix; i++) {
		if (radio[i].checked) {
			res = radio[i].value;
		}
	}
	return res;
}
/**
 * Retourne 1 si l'option sélectionnée a pour valeur 0 (en général associée à --
 * Choisissez une valeur ---) retourne 0 sinon
 * 
 * @param nomSelect
 *            (nom du select)
 * @return 0|1
 * @since 06/06/2011
 */
function emptySelect(nomSelect) {
	selectElement = document.getElementById(nomSelect);
	return Number(selectElement.options[selectElement.selectedIndex].value == 0);
}
/**
 * Retourne la valeur de l'option sélectionnée dans le select
 * 
 * @param nomSelect
 *            (nom du select)
 * @return valeur de l'option sélectionnée
 * @since 06/06/2011
 */
function valeurSelect(nomSelect) {
	selectElement = document.getElementById(nomSelect);
	return selectElement.options[selectElement.selectedIndex].value;
}
/**
 * Retourne 1 si needle est dans haystack 0 sinon
 * 
 * @param needle
 *            (valeur cherchée)
 * @param haystack
 *            (tableau dans lequel on recherche)
 * @return 0|1 (par transformation du booléen)
 * @since 06/06/2011
 */
function inArray(needle, haystack) {
	var sentinelle = haystack.length - 1;
	haystack[sentinelle] = needle;
	var i = 0;
	while (!(haystack[i] == needle))
		i++;
	return Number(i < sentinelle);
}
/**
 * fonction appelée par onchange sur le Select de l'établissement
 */
function onchangeCodeEN(tabNiveaux, tabTarifs, tabClasses, codeEN) {
	// insertion du blocHtmlStation
	putsSelectStations();
	// insertion du blocHtmlClasse
	var blocHtml = blocHtmlClasse(tabNiveaux, tabClasses, codeEN);
	document.getElementById('spamCodeClasse').innerHTML = blocHtml;
	// insertion du blocHtmlTarif
	blocHtml = blocHtmlTarif(tabNiveaux, tabTarifs, codeEN);
	document.getElementById('spamCodeTarif').innerHTML = blocHtml;
	montreBloc('rib', 0);
}
/**
 * Construction de la liste déroulante des stations
 */
function putsSelectStations() {
	var codeEN = valeurSelect('CodeEN');
	var nomsSelect = new Array('CodeStationR1', 'CodeStationR2');
	for ( var i = 0; i < 2; i++) {
		// récupère le CommuneR(i+1)
		var codeVille = valeurSelect('CommuneR' + (i + 1));
		var blocHtml = '<select name="' + nomsSelect[i] + '" id="'
				+ nomsSelect[i] + '">';
		if (codeEN == 0) {
			blocHtml += '\n  <option value="0" label="--- Choisissez d\'abord l\'établissement ---">--- Choisissez d\'abord l\'établissement ---</option>';
		} else {
			if (codeVille == 0) {
				blocHtml += '\n  <option value="0" label="--- Choisissez d\'abord la commune ---">--- Choisissez d\'abord la commune ---</option>';
			} else {
				blocHtml += '\n  <option value="0" label="--- Choisissez un point d\'arrêt ---">--- Choisissez un point d\'arrêt ---</option>';
				var valHidden = document.getElementById('h' + nomsSelect[i]).value;
				if (tabStations[codeEN][codeVille] == undefined) {					
					for (commune in tabStations[codeEN]) {
						nb = tabStations[codeEN][commune].length;
						for ( var j = 0; j < nb; j++) {
							blocHtml += '\n  <option value="'
									+ tabStations[codeEN][commune][j][0];
							if (tabStations[codeEN][commune][j][0] == valHidden) {
								blocHtml += '" selected="selected';
							}
							blocHtml += '">'
									+ tabStations[codeEN][commune][j][1]
									+ '</option>';
						}
					}
				} else {
					nb = tabStations[codeEN][codeVille].length;
					for ( var j = 0; j < nb; j++) {
						blocHtml += '\n  <option value="'
								+ tabStations[codeEN][codeVille][j][0];
						if (tabStations[codeEN][codeVille][j][0] == valHidden) {
							blocHtml += '" selected="selected';
						}
						blocHtml += '">' + tabStations[codeEN][codeVille][j][1]
								+ '</option>';
					}
				}
			}
		}
		blocHtml += '\n</select>';
		idSpam = 'spam' + nomsSelect[i];
		document.getElementById(idSpam).innerHTML = blocHtml;
	}
}

function blocHtmlClasse(tabniveaux, tab, codeEN) {
	var blocHtml = 'vide';
	if (codeEN != 'vide') {
		// on cherche le niveau pour les classes
		var nb = tabniveaux.length;
		var val = 'vide';
		for ( var j = 0; j < nb; j++) {
			if (tabniveaux[j][0] == codeEN) {
				val = tabniveaux[j][2];
				break;
			}
		}
		if (val != 'vide') {
			// on compte les classes
			nb = tab[val].length;
			valHidden = document.getElementById('hCodeClasse').value;
			// construction du blocHtml à insérer
			blocHtml = '<select name="Classe" id="Classe">';
			blocHtml += '\n    <option value="0" label="--- Choisissez la classe ---">--- Choisissez la classe ---</option>';
			for ( var j = 0; j < nb; j++) {
				blocHtml += '\n    <option value="' + tab[val][j][0];
				// + '" label="' + tab[val][j][1];
				if (tab[val][j][0] == valHidden) {
					blocHtml += '" selected="selected';
				}
				blocHtml += '">' + tab[val][j][1] + '</option>';
			}
			blocHtml += '\n</select>';
		}
	}
	if (blocHtml == 'vide') {
		blocHtml = '<select name="Classe" id="Classe">';
		blocHtml += '	    <option value="0" label="--- Choisissez d\'abord l\'établissement ---">--- Choisissez d\'abord l\'établissement ---</option>';
		blocHtml += '</select>';
	}
	return blocHtml;
}

function blocHtmlTarif(tabniveaux, tab, codeEN) {
	var blocHtml = 'vide';
	if (codeEN != 'vide') {
		// on cherche le niveau pour les tarifs
		var nb = tabniveaux.length;
		var val = 'vide';
		for ( var j = 0; j < nb; j++) {
			if (tabniveaux[j][0] == codeEN) {
				val = tabniveaux[j][1];
				break;
			}
		}
		if (val != 'vide') {
			// on compte les tarifs
			nb = tab[val].length;
			valHidden = document.getElementById('hCodeTarif').value;
			// construction du blocHtml à insérer
			blocHtml = '<select name="CodeTarif" id="CodeTarif" onchange="onchangeCodeTarif(tabTarifs,this.value);">';
			blocHtml += '\n    <option value="0" label="--- Choisissez le tarif ---">--- Choisissez le tarif ---</option>';
			for ( var j = 0; j < nb; j++) {
				blocHtml += '\n    <option value="' + tab[val][j][0];
				// + '" label="' + tab[val][j][1];
				if (tab[val][j][0] == valHidden) {
					blocHtml += '" selected="selected';
				}
				blocHtml += '">' + tab[val][j][1] + ' (' + tab[val][j][2]
						+ ' €)</option>';
			}
			blocHtml += '\n</select>';
		}
	}
	if (blocHtml == 'vide') {
		blocHtml = '<select name="CodeTarif" id="CodeTarif">';
		blocHtml += '	    <option value="0" label="--- Choisissez d\'abord l\'établissement ---">--- Choisissez d\'abord l\'établissement ---</option>';
		blocHtml += '</select>';
	}
	return blocHtml;
}


function onchangeCodeTarif(tab, codetarif) {
	var prelevement = -1; // par la suite, prend les valeurs O ou 1
	var ni = tab.length;
	for ( var i = 1; i < ni; i++) {
		var nj = tab[i].length;
		for ( var j = 0; j < nj; j++) {
			if (tab[i][j][0] == codetarif) {
				prelevement = tab[i][j][3];
				break;
			}
		}
		if (prelevement != -1) {
			break;
		}
	}
	montreBloc('rib', prelevement == 1 ? 1 : 0);
}

function blocHtmlStation(tab, codeInsee, idSelect) {
	var blocHtml = 'vide';
	var val = -1;
	if (codeInsee != 'vide') {
		// on cherche la ville
		var nb = tab.length;
		for ( var j = 0; j < nb; j++) {
			if (tab[j][0][0] == codeInsee) {
				val = j;
				break;
			}
		}
		if (val != -1) {
			// on compte les stations
			var nb = tab[val][1].length;
			valHidden = document.getElementById('h' + idSelect).value;
			// construction du blocHtml à insérer
			blocHtml = '<select name="' + idSelect + '" id="' + idSelect + '">';
			blocHtml += '\n    <option value="0">--- Choisissez le point d\'arrêt ---</option>';
			// blocHtml += '\n <option value="0" label="--- Choisissez le point
			// d\'arrêt ---">--- Choisissez le point d\'arrêt ---</option>';
			for ( var j = 0; j < nb; j++) {
				blocHtml += '\n    <option value="' + tab[val][1][j][0];
				// + '" label="' + tab[val][1][j][1];
				if (tab[val][1][j][0] == valHidden) {
					blocHtml += '" selected="selected';
				}
				blocHtml += '">' + tab[val][0][1] + ' - ' + tab[val][1][j][1]
						+ '</option>';
			}
			blocHtml += '\n</select>';
		}
	}
	if (blocHtml == 'vide') {
		blocHtml = '<select name="' + idSelect + '" id="' + idSelect + '">';
		blocHtml += '    <option value="0" label="--- Choisissez d\'abord la commune ---">--- Choisissez d\'abord la commune ---</option>';
		blocHtml += '</select>';
	}
	return blocHtml;
}
function onchangeCommuneR1(tab, codeInsee) {
	// var blocHtml = blocHtmlStation(tab, codeInsee, 'CodeStationR1');
	// insertion du blocHtml
	// document.getElementById('spamCodeStationR1').innerHTML = blocHtml;
	putsSelectStations();
}
function onchangeCommuneR2(tab, codeInsee) {
	// var blocHtml = blocHtmlStation(tab, codeInsee, 'CodeStationR2');
	// insertion du blocHtml
	// document.getElementById('spamCodeStationR2').innerHTML = blocHtml;
	putsSelectStations();
}
/**
 * Montre ou cache un bloc d'éléments (ou un élément)
 * 
 * @param idBloc
 *            (Id du bloc ou de l'élément à cacher)
 * @param montre
 *            (1 pour montrer, 0 pour cacher)
 * @return
 */
function montreBloc(idBloc, montre) {
	if (idBloc == "gardeAlternee") {
	  cacheBloc = document.getElementById('SecondeAdresse-0').checked == true;
	  document.getElementById(idBloc).style.display = cacheBloc && (montre == 0) ? 'none' : '';
	} else {
		if (idBloc == 'rib') {
			document.getElementById(idBloc).style.display = cacheRib()
					|| (montre == 0) ? 'none' : '';
		} else {
			document.getElementById(idBloc).style.display = montre == 0 ? 'none' : '';
		}
	}
}

function cacheRib() {
	var cache = document.getElementById('RibBanque') == '';
	cache &= document.getElementById('RibAgence') == '';
	cache &= document.getElementById('RibCompte') == '';
	cache &= document.getElementById('RibCle') == '';
	cache &= document.getElementById('RibDom') == '';
	cache &= document.getElementById('RibTit') == '';
	return cache;
}
