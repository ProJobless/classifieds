<?php

class User_RegistrationController extends Bisna\Controller\Action
{
    public function signupAction()
    {
        if($this->getRequest()->getMethod() === 'POST' && !$this->_getParam("status"))
            return $this->forward('post');

        $this->view->messages = $this->_getParam("messages");
        $this->view->messages_class = $this->_getParam("messages_class");
        $this->view->user = $user = $this->_getParam("user");
    }

    public function postAction()
    {
        $request = $this->_getParam("user");
        try
        {
            $valid = $this->em()->getRepository('User\Entity\User')->validate($request);

            $messages = ($valid === true)? array() : $valid;
            if (! empty($messages))
                throw new Zend_Exception("Validation errors");

            // create user
            $user = $this->em()->getRepository('User\Entity\User')->create($request);
            $this->em()->flush();

            // write in session
            $data = array("id" => $user->getId());
            $this->em()->getRepository('\User\Entity\Session')->write($data);

            $this->_helper->messages("SIGNED_UP_OK", "success");
            $this->_helper->redirector->gotoRoute(array(), "home", true);
        }
        catch (Zend_Exception $e)
        {
            $options = array(
                "status" => "error",
                "messages" => $messages,
                "messages_class" => "error",
            );
            $this->forward("signup", null, null, $options);
        }
    }

}