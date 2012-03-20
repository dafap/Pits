/**
 * Les scripts du projet PiTS
 */

/**
 * Pour faire fonctionner l'aspect hover dans un IE
 * 
 * @param obj (
 *            objet sur lequel porte l'aspect hover)
 * @param className (
 *            nom de la classe css à appliquer)
 * @return void
 */
function jsHover(obj, className) {
	if (navigator.appName == 'Microsoft Internet Explorer') {
		obj.className = className;
	}
}
/**
 * Montre ou cache un bloc d'éléments (ou un élément)
 * 
 * @param idBloc
 *            (Id du bloc ou de l'élément à cacher)
 * @param montre
 *            (1 pour montrer, 0 pour cacher)
 * @return void
 */
function montreBloc(idBloc, montre) {
	document.getElementById(idBloc).style.display = montre == 0 ? 'none' : '';
}
/**
 * Retourne la valeur du bouton radio sélectionné
 * 
 * @param nomElement
 *            (nom du bouton radio)
 * @param nbChoix
 *            (nombre de choix dans le bouton radio)
 * @return valeur du choix sélectionné
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
 * Retourne 1 si l'option sélectionnée a pour valeur -1 (en général associée à --
 * Choisissez une valeur ---) 0 sinon
 * 
 * @param nomSelect
 *            (nom du select)
 * @return 0|1
 */
function emptySelect(nomSelect) {
	selectElement = document.getElementById(nomSelect);
	return Number(selectElement.options[selectElement.selectedIndex].value == -1);
}
/**
 * Retourne la valeur de l'option sélectionnée dans le select
 * 
 * @param nomSelect
 *            (nom du select)
 * @return valeur de l'option sélectionnée
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
 */
function inArray(needle, haystack) {
	if (haystack.length == 0)
		return 0;
	
	var sentinelle = haystack.length - 1;
	haystack[sentinelle] = needle;
	var i = 0;
	while (!(haystack[i] == needle))
		i++;
	return Number(i < sentinelle);
}

/*******************************************************************************
 * fonctions spéciales pour formulaire eleve
 ******************************************************************************/

/**
 * Paramètre : le nom du select (CodeStation1 ou CodeStation2) Retourne la
 * valeur 1 si l'arrêt sélectionné est dans la COBAS, 0 sinon Necessite la
 * présence du tableau tabStationsHorsCobas des CodeStation hors COBAS
 * 
 * @param nomSelect
 *            (nom du select : 'CodeStation1' ou 'codeStation2')
 * @return 0|1 (1 si la valeur sélectionnée dans le select n'est pas dans le
 *         tableau)
 */
function getCobas(nomSelect) {
	arret = valeurSelect(nomSelect);
	return 1 - inArray(arret, tabStationsHorsCobas);
}

/**
 * Retourne 1 si l'établissement sélectionné dans le select 'CodeEN' est un
 * établissement d'Arcachon 0 sinon Nécessite la présence du tableau
 * tabEcolesArcachon des CodeEN des établissements scolaire d'Arcachon
 * 
 * @return 0|1
 */
function inArcachon() {
	codeEN = valeurSelect('CodeEN');
	return inArray(codeEN, tabEcolesArcachon);
}

/**
 * Retourne 1 si la valeur du hidden hFamille est supérieur à 2 (au moins 3
 * enfants) 0 sinon Nécessite la présence d'un hidden hFamille contenant le
 * nombre d'enfants (y compris celui en cours d'édition)
 * 
 * @return 0|1
 */
function getFamille() {
	return Number(document.getElementById('hFamille').value > 2);
}

/**
 * Retourne 1 si le radio SecondeAdresse est sur Oui 0 s'il est sur Non
 * 
 * @return 0|1
 */
function getSecondeAdresse() {
	return valeurRadio('SecondeAdresse', 2);
}

/**
 * Calcule le typeTarif -1 -- Choisissez d'abord le point d'arrêt -- 1 tarif
 * COBAS normal 2 tarif COBAS famille 3 tarif hors COBAS Arcachon 4 tarif hors
 * COBAS Gujan (et aussi La Teste, Le Teich, Pyla, Cazeaux)
 * 
 * @return [1-4]
 */
function getTypeTarif() {
	var cobas;
	var ecole;
	var famille;
	var tarif;
	var typeTarif;

	if (emptySelect('CodeStation1') || emptySelect('CodeEN'))
		return -1;

	if (getSecondeAdresse() == 1) {
		cobas = getCobas('CodeStation1') * getCobas('CodeStation2');
	} else {
		cobas = getCobas('CodeStation1');
	}
	ecole = inArcachon();
	famille = getFamille();
	typeTarif = (1 - cobas) * (4 - ecole) + cobas * (1 + famille);
	return typeTarif;
}
/**
 * Construction de la liste déroulante des tarifs
 */
function putsSelectTarifs() {
	// Recherche le typeTarif
	var typeTarif = getTypeTarif();
	
	// Préparation du blocHtml 'select' à insérer
	var blocHtml = '<select name="CodeTarif" id="CodeTarif" class="field_select_275">';

	// Mise en place des options
	if (typeTarif == -1) {
		blocHtml += '\n  <option value="-1" label="--- Choisissez les points d\'arrêt ---">--- Choisissez les points d\'arrêt ---</option>';
	} else {
		blocHtml += '\n  <option value="-1" label="--- Choisissez un tarif ---">--- Choisissez un tarif ---</option>';
		// valeur du hidden hCodeTarif
		var valHidden = document.getElementById('hCodeTarif').value;
		// ajout des options
		nb = tabTarifs[typeTarif].length;
		for (var j = 0; j < nb; j++) {
			blocHtml += '\n    <option value="' + tabTarifs[typeTarif][j][0];
			// + '" label="' + tab[val][j][1];
			if (tabTarifs[typeTarif][j][0] == valHidden) {
				blocHtml += '" selected="selected';
			}
			blocHtml += '">' + tabTarifs[typeTarif][j][1] + ' (' + tabTarifs[typeTarif][j][2] + ' €)</option>';
		}
	}
	blocHtml += '\n</select>';
	
	// Insertion du blocHtml
	document.getElementById('spamCodeTarif').innerHTML = blocHtml;
}

/**
 * Construction de la liste déroulante des stations
 */
function putsSelectStations() {
	var codeEN = valeurSelect('CodeEN');
	var nomsSelect = new Array('CodeStation1', 'CodeStation2');
	for (var i=0; i<2; i++) {
		var blocHtml = '<select name="' + nomsSelect[i] + '" id="' + nomsSelect[i] + '" class="field_select_275" onchange="onchangeCodeStation();">';
		if (codeEN == -1) {
			blocHtml += '\n  <option value="-1" label="--- Choisissez d\'abord l\'établissement ---">--- Choisissez d\'abord l\'établissement ---</option>';
		} else {
			blocHtml += '\n  <option value="-1" label="--- Choisissez un point d\'arrêt ---">--- Choisissez un point d\'arrêt ---</option>';
			var valHidden = document.getElementById('h' + nomsSelect[i]).value;
			nb = tabStations[codeEN].length;
			for (var j=0; j < nb; j++) {
				blocHtml += '\n  <option value="' + tabStations[codeEN][j][0];
				if (tabStations[codeEN][j][0] == valHidden) {
					blocHtml += '" selected="selected';
				}
				blocHtml += '">' + tabStations[codeEN][j][1] + '</option>';
			}
		}
		blocHtml += '\n</select>';
		idSpam = 'spam' + nomsSelect[i];
		document.getElementById(idSpam).innerHTML = blocHtml;
    }
}
/**
 * Sur changement de l'établissement
 */
function onchangeCodeEN() {
	putsSelectStations();
	putsSelectTarifs();
}
/**
 * Sur changement des stations
 */
function onchangeCodeStation() {
	putsSelectTarifs();
}
/*******************************************************************************
 * Définir les tableaux suivants
 ******************************************************************************/
/**
 * Tableau des stations hors Cobas (CodeStation) exemple : var tabStationsHorsCobas =
 * new Array(273,274,276,337,338); Penser à créer la place réservée à la
 * sentinelle exemple : tabStationsHorsCobas[tabStationsHorsCobas.length] = 0;
 */

/**
 * Tableau des établissements scolaires d'Arcachon (CodeEN) exemple : var
 * tabEcolesArcachon = new Array('0330003Z', '0330167C', '0330216F', '0330217G',
 * '0330335K', '0330337M', '0330340R', '0331488N', '0331767S', '0332194F',
 * '0332330D', '0332487Z', '0332604B', '0332889L'); Penser à créer la place
 * réservée à la sentinelle exemple :
 * tabEcolesArcachon[tabEcolesArcachon.length] = '';
 */
