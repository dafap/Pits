<?php
/**
 * Validateur pour confirmer le mdp
 *
 * La confirmation est passée dans le constructeur par la méthode isValid($data) du formulaire
 * $this->getElement('password')->addValidator(new Pits_Validate_PasswordMatch($data['password2']));
 *
 * @author source provenant de http://pastie.org/640447*
 * @project télé-paiement CCM
 * @package application/forms/validators
 * @date 20 sept. 2011
 * @version $Id: $
 */

require_once 'Zend/Validate/Abstract.php';

class Tp_Validate_PasswordMatch extends Zend_Validate_Abstract
{
    const PASSWORD_MISMATCH = 'passwordMismatch';

    protected $_compare;

    public function __construct($compare)
    {
        $this->_compare = $compare;
    }

    protected $_messageTemplates = array(
       self::PASSWORD_MISMATCH => "Le mot de passe ne correspond pas à la confirmation"
    );

    public function isValid($value)
    {
        $this->_setValue((string) $value);

        if ($value !== $this->_compare)  {
            $this->_error(self::PASSWORD_MISMATCH);
            return false;
        }

        return true;
    }
}