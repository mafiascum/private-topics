<?php

namespace mafiascum\privatetopics\controller;

class verifyUsername
{
    /* @var \phpbb\request\request */
    protected $request;

    /* @var \phpbb\user_loader */
    protected $user_loader;
    
    public function __construct(\phpbb\request\request $request, \phpbb\user_loader $user_loader)
    {
        $this->request = $request;
        $this->user_loader = $user_loader;
    }

    public function handle()
    {
        $username = $this->request->variable('q', '');
        
        $user_id = $this->user_loader->load_user_by_username($username);

        if ($user_id == ANONYMOUS) {
            return new \Symfony\Component\HttpFoundation\JsonResponse(array());
        } else {
            $username_formatted = $this->user_loader->get_username($user_id, 'username');
            $username_profile = $this->user_loader->get_username($user_id, 'profile');

            return new \Symfony\Component\HttpFoundation\JsonResponse(array(
                array(
                    'user_id'  => $user_id,
                    'username' => $username_formatted,
                    'profile'  => $username_profile,
                )
            ));
        }
    }
}