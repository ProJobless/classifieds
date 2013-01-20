<?php

class User_ProfileController extends Bisna\Controller\Action
{
    // show user profile
    public function indexAction()
    {
        // user exists
        $id = $this->_getParam("id");
        $user = $this->em()->find('\User\Entity\User', $id);
        if (is_null($user)) {throw new Zend_Exception("ERROR", 404);}

        $this->view->user = $user->getArrayCopy();
    }

    // edit user profile
    public function editAction()
    {
        $id = $this->_getParam("id");
        $user = $this->em()->find('\User\Entity\User', $id);

        // access
        if (is_null($user)) {throw new Zend_Exception("ERROR", 404);}
        $this->_helper->checkMember(); // logged in
        $this->_helper->checkOwner($id); // is profile owner || is admin

        if($this->getRequest()->getMethod() === 'POST')
        {
            try
            {
                $request = $this->_getParam("user");

                $valid = $this->em()->getRepository('User\Entity\User')
                    ->validate($request, $password_req = false);

                $messages = ($valid === true)? array() : $valid;
                if (! empty($messages))
                    throw new Zend_Exception("Validation errors");

                // update user
                $user->populate($request);
                $this->em()->persist($user);
                $this->em()->flush();

                $this->_helper->messages("PROFILE_UPDATED_OK", "success");
                $this->_helper->redirector
                    ->gotoRoute(array("id"=>$request['id']), "profile", true);
            }
            catch (Zend_Exception $e)
            {
                $this->view->messages = $messages;
                $this->view->messages_class = "error";
            }
        }

        $this->view->user = $this->_getParam("user", $user->getArrayCopy());
    }

    public function settingsAction()
    {
        $id = $this->_getParam("id");
        $user = $this->em()->find('\User\Entity\User', $id);

        // access
        if (is_null($user)) {throw new Zend_Exception("ERROR", 404);}
        $this->_helper->checkMember(); // logged in
        $this->_helper->checkOwner($id); // is profile owner || is admin

        if($this->getRequest()->getMethod() === 'POST')
        {
            $request = $this->_getParam("user");

            try
            {
                // validations
                $messages = array();

                $email_validator = new Zend_Validate_EmailAddress();
                if (empty($request['email'])) // email must be set
                    $messages[] = "EMAIL_BLANK";
                elseif (! $email_validator->isValid($request['email'])) // & must be valid email
                    $messages[] = "EMAIL_NOT_VALID";
                elseif ($this->em()->getRepository('\User\Entity\User')->isTaken($request['email'], $id)) // & must be unique
                    $messages[] = "EMAIL_TAKEN";

                if (! empty($request['password_new']))
                {
                    if (strlen($request['password_new']) < 4) // must be at least 4 chars long
                        $messages[] = "PASSWORD_TOO_SHORT";
                    if ($request['password_confirmation'] != $request['password_new'])
                        $messages[] = "PASSWORDS_DONT_MATCH";
                }

                if (! empty($messages))
                    throw new Zend_Exception("Validation errors");

                // request is valid
                if ($request['email'] != $user->getEmail())
                {
                    $user->setEmail($request['email']);
                    $user->setVerified(false);
                }

                if (! empty($request['password_new']))
                {
                    $salt = md5(rand());
                    $user->setSalt($salt);
                    $user->setPassword(md5($salt . $request['password_new']));
                }

                $user->setAllowLetters($request['allow_letters']);

                $this->em()->persist($user);
                $this->em()->flush();

                $this->_helper->messages("PROFILE_UPDATED_OK", "success");
                $this->_helper->redirector
                    ->gotoRoute(array("id"=>$request['id']), "profile", true);
            }
            catch (\Zend_Exception $e)
            {
                $this->view->messages = $messages;
                $this->view->messages_class = "error";
            }
        }

        $this->view->user = $this->_getParam("user", $user->getArrayCopy());
    }
}
