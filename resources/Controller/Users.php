<?php
/**
 * Description of Users
 * 
 * @author gullo
 */
class Controller_Users extends MyFw_Controller {
    
    function _init() {
        $auth = Zend_Auth::getInstance();
        $this->_iduser = $auth->getIdentity()->iduser;
        $this->_userSessionVal = new Zend_Session_Namespace('userSessionVal');
    }

    
    function indexAction() {
        $this->redirect("gruppo", "iscritti");
    }
    
    function editAction() {
        $iduser = $this->getParam("iduser");
        
        // check if CAN edit this Produttore
        $uObj = new Model_Users();
        $user = $uObj->getUserByIdInGroup($iduser, $this->_userSessionVal->idgroup);
        if($user === false) {
            $this->redirect("gruppo", "iscritti");
        }
        $this->view->user = $user;
        
        $this->view->updated = false;
        
        $form = new Form_User();
        $form->setAction("/users/edit/iduser/$iduser");
        // remove useless fields
        $form->removeField("password");
        $form->removeField("password2");
        $form->removeField("idgroup");
        
        // Get Elenco produttori (con REFERENTE)
        $prObj = new Model_Produttori();
        $this->view->produttori = $prObj->getProduttoriByIdGroup($this->_userSessionVal->idgroup);
        //Zend_Debug::dump($this->view->user);

        if($this->getRequest()->isPost()) {
            $fv = $this->getRequest()->getPost();
            if( $form->isValid($fv) ) {

                $values = $form->getValues();
                // remove users_group fields
                $attivo = $values["attivo"];
                unset($values["attivo"]);
                $fondatore = $values["fondatore"];
                unset($values["fondatore"]);
                $contabile = $values["contabile"];
                unset($values["contabile"]);
                
                // SAVE user data
                $this->getDB()->makeUpdate("users", "iduser", $values);
                
                // SET users_group flags
                $sth = $this->getDB()->prepare("UPDATE users_group SET attivo= :attivo, fondatore= :fondatore, contabile= :contabile WHERE iduser= :iduser AND idgroup= :idgroup");
                $fields = array('attivo' => $attivo, 'fondatore' => $fondatore, 'contabile' => $contabile, 'iduser' => $iduser, 'idgroup' => $this->_userSessionVal->idgroup);
                $sth->execute($fields);
                
                $this->view->updated = true;
            }
            
            
        } else {
            $form->setValues($user);
        }
        // Zend_Debug::dump($form); die;
        // set Form in the View
        $this->view->form = $form;
        
    }

    function addrefAction() {
        
        $layout = Zend_Registry::get("layout");
        $layout->disableDisplay();
        
        $idproduttore = $this->getParam("idproduttore");
        $iduser = $this->getParam("iduser");
        
        $sth = $this->getDB()->prepare("SELECT * FROM groups_produttori WHERE idproduttore= :idproduttore AND idgroup= :idgroup");
        $arVal = array('idproduttore' => $idproduttore, 'idgroup' => $this->_userSessionVal->idgroup);
        $sth->execute($arVal);
        if($sth->rowCount() > 0) {
            // UPDATE
            $sth_update = $this->getDB()->prepare("UPDATE groups_produttori SET iduser_ref= :iduser_ref WHERE idproduttore= :idproduttore AND idgroup= :idgroup");
            $arVal["iduser_ref"] = $iduser;
            $result = $sth_update->execute($arVal);
        } else {
            // Non dovrebbe mai capitare: un produttore ha SEMPRE un referente!
            $result = false;
        }

        echo json_encode($result);
    }
    
    function enableAction() {
        $layout = Zend_Registry::get("layout");
        $layout->disableDisplay();
        
        $iduser = $this->getParam("iduser");
        $uObj = new Model_Users();
        $user = $uObj->getUserByIdInGroup($iduser, $this->_userSessionVal->idgroup);
        $result = false;
        if(!is_null($user)) {
            // send email to User
            $gObj = new Model_Groups();
            $mail = new MyFw_Mail();
            $group = $gObj->getGroupById($this->_userSessionVal->idgroup);
            $mail->setSubject("Iscrizione al Gruppo ".$group->nome);
            $mail->addTo($user->email);
            $ugObj = $gObj->getGroupFoundersById($this->_userSessionVal->idgroup);
            foreach($ugObj AS $ugVal) {
                $mail->addBcc($ugVal["email"]);
            }
            $mail->setViewParam("new_user", $user->nome . " " . $user->cognome );
            $mail->setViewParam("gruppo", $group->nome);
            $mail->setViewParam("email_user", $user->email);
            $config = Zend_Registry::get("appConfig");
            $mail->setViewParam("url_environment", $config->url_environment);
            $sended = $mail->sendHtmlTemplate("registration.new_user_enabled.tpl.php");
            if($sended) {
                // enable user
                $sth_update = $this->getDB()->prepare("UPDATE users_group SET attivo='S' WHERE iduser= :iduser AND idgroup= :idgroup");
                $result = $sth_update->execute(array('iduser' => $iduser, 'idgroup' => $this->_userSessionVal->idgroup));
            }
        }
        echo json_encode($result);
    }
    
}