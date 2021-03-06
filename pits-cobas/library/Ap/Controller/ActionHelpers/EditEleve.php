<?php
/**
 * Aide d'action pour éditer la fiche d'un élève (création ou modification)
 *
 * Pour l'aide d'action EditEleve, passer dans l'ordre :
 * - l'url `action` du formulaire
 * - l'url `forward` après traitement
 * - les élements de l'url du bouton 'cancel' sous forme d'un array('controller'=>..., 'action'=>...)
 * - l'enregistrement Zend_Db_Table_Row_Abstract de la bd
 *
 * @project pits
 * @package package_name
 * @author DAFAP Informatique - Alain Pomirol (dafap@free.fr)
 * @date 25 mai 2011
 * @version $Id: $
 */

class Ap_Controller_ActionHelpers_EditEleve extends Zend_Controller_Action_Helper_Abstract
{
    public function direct($nbEnfants, $actionForm, $cancel, Pits_Model_DbTable_Row_TEleves $record)
    {
        $dateN = empty($record->DateN) ? '' : Pits_Model_Format::date('dd/MM/YYYY',$record->DateN);

        $configForm = new Zend_Config_Ini(Zend_Registry::get('applicationPath') . '/configs/forms.ini', 'ficheleve');
        $form = new Pits_Form_Eleve($configForm->ap->ficheleve);

        $form->setAction($actionForm)
        ->setEtablissements()
        ->setStations()
        ->setTarifs()
        ->setVilles()
        ->setActionRetour($cancel)
        ->setDefaults($record->toArray())
        ->setDefault('DateN', $dateN)
        ->setDefaults(array('hCodeStation1' => $record->CodeStation1, 'hCodeStation1m' => $record->CodeStation1m, 'hCodeStation1s' => $record->CodeStation1s,
                            'hCodeStation2' => $record->CodeStation2, 'hCodeStation2m' => $record->CodeStation2m, 'hCodeStation2s' => $record->CodeStation2s, ))
        ->setDefault('hCodeTarif', $record->CodeTarif)
        ->setDefault('hSecondeAdresse', $record->SecondeAdresse)
        ->setDefault('hFamille', $nbEnfants);
        if ($this->getRequest()->isPost()) {
            if ($form->isValid($_POST)) {
                // valeurs renvoyées par le formulaire
                $formValues = $form->getValues();
                // formatage des valeurs à enregistrer
                $formValues['DateN'] = Pits_Model_Format::date('YYYY-MM-dd', $formValues['DateN'], 'fr_FR');
                if ($formValues['CodeStation1m'] == -1) {
                    $formValues['CodeStation1m'] = null;
                    $formValues['CodeService1m'] = null;
                }
                if ($formValues['CodeStation1s'] == -1) {
                    $formValues['CodeStation1s'] = null;
                    $formValues['CodeService1s'] = null;
                }
                if ($formValues['SecondeAdresse'] == 0) {
                    $formValues['CodeStation2'] = null;
                    $formValues['CodeService2'] = null;
                    $formValues['CodeStation2m'] = null;
                    $formValues['CodeService2m'] = null;
                    $formValues['CodeStation2s'] = null;
                    $formValues['CodeService2s'] = null;
                    $formValues["TitreR2"] = null;
                    $formValues["NomR2"] = null;
                    $formValues["PrenomR2"] = null;
                    $formValues["AdressR2L1"] = null;
                    $formValues["AdressR2L2"] = null;
                    $formValues["CommuneR2"] = null;
                    $formValues["CodePostalR2"] = null;
                    $formValues["TelephoneR2"] = null;
                    $formValues["TelephoneR2c"] = null;
                    $formValues["EmailR2"] = null;
                } else {
                    if ($formValues['CodeStation2'] == -1) {
                        $formValues['CodeStation2'] = null;
                        $formValues['CodeService2'] = null;
                    }
                    if ($formValues['CodeStation2m'] == -1) {
                        $formValues['CodeStation2m'] = null;
                        $formValues['CodeService2m'] = null;
                    }
                    if ($formValues['CodeStation2s'] == -1) {
                        $formValues['CodeStation2s'] = null;
                        $formValues['CodeService2s'] = null;
                    }
                }
                
                // suppression des champs de controle
                $values = array_intersect_key($formValues, $record->toArray());
                // valeurs modifiées
                $record->setFromArray(array_diff_assoc($values, $record->toArray()));
                // faut-il enregistrer ?
                if ($record->isModified()) {
                    // le champs CodeStation1 a-t-il changé ?
                    if ($majService1 = $record->isFieldModified('CodeStation1')) { // affectation simultanée
                        $record->CodeService1 = null; // on libère la place pour ré-affectation, nécessaire si changement de station sur le même circuit
                    }
                    // le champs CodeStation1m a-t-il changé ?
                    if ($majService1m = $record->isFieldModified('CodeStation1m')) { // affectation simultanée
                        $record->CodeService1m = null; // on libère la place pour ré-affectation, nécessaire si changement de station sur le même circuit
                    }
                    // le champs CodeStation1s a-t-il changé ?
                    if ($majService1s = $record->isFieldModified('CodeStation1s')) { // affectation simultanée
                        $record->CodeService1s = null; // on libère la place pour ré-affectation, nécessaire si changement de station sur le même circuit
                    }
                    // le champs CodeStation2 a-t-il changé ?
                    if ($majService2 = $record->isFieldModified('CodeStation2')) { // affectation simultanée
                        $record->CodeService2 = null; // on libère la place pour ré-affectation, nécessaire si changement de station sur le même circuit
                    }
                    // le champs CodeStation2m a-t-il changé ?
                    if ($majService2m = $record->isFieldModified('CodeStation2m')) { // affectation simultanée
                        $record->CodeService2m = null; // on libère la place pour ré-affectation, nécessaire si changement de station sur le même circuit
                    }
                    // le champs CodeStation2s a-t-il changé ?
                    if ($majService2s = $record->isFieldModified('CodeStation2s')) { // affectation simultanée
                        $record->CodeService2s = null; // on libère la place pour ré-affectation, nécessaire si changement de station sur le même circuit
                    }
                    try {
                        $record->save();
                        $tservices = new Pits_Model_DbTable_TServicesFromEtablissementStation();
                        if ($majService1) {
                            $record->setData('CodeService1', $tservices->findService($record->CodeEN, $record->CodeStation1));
                        }
                        if ($majService1m) {
                            $record->setData('CodeService1m', $tservices->findService($record->CodeEN, $record->CodeStation1m));
                        }
                        if ($majService1s) {
                            $record->setData('CodeService1s', $tservices->findService($record->CodeEN, $record->CodeStation1s));
                        }
                        if ($majService2) {
                            $record->setdata('CodeService2', $tservices->findService($record->CodeEN, $record->CodeStation2));
                        }
                        if ($majService2m) {
                            $record->setData('CodeService2m', $tservices->findService($record->CodeEN, $record->CodeStation2m));
                        }
                        if ($majService2s) {
                            $record->setData('CodeService2s', $tservices->findService($record->CodeEN, $record->CodeStation2s));
                        }
                    } catch (Exception $e) {
                    }
                }
                return true;
            } else {
                $bootstrap = $this->getActionController()->getInvokeArg('bootstrap');
                $log = $bootstrap->getResource('log');
                $user = Zend_Auth::getInstance()->getIdentity();
                $log->info(__FILE__ . ':' . __CLASS__ . '(' . __LINE__ . ") user: $user->Titre $user->Nom $user->Prenom");
                foreach ($form->getValues() as $key => $value) {
                    $log->debug("$key: $value");
                }
                $this->getActionController()->view->form = $form;
                return false;
            }
        } else {
            $this->getActionController()->view->form = $form;
            return false;
        }
    }
    /**
     * Pour utiliser l'aide d'action redirector
     *
     * @param string $url
     */
    private function _redirect($url)
    {
        Zend_Controller_Action_HelperBroker::getStaticHelper('Redirector')->gotoUrl($url);
    }
}