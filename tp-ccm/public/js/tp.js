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