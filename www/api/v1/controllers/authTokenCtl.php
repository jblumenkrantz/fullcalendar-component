<?php

class AuthTokenCtl 
{
	public function get ($user_handle, $password) {
		
		try {
			$user = User::loadWithHandle($user_handle, FALSE);
			if (!$user)
				throw new UserDoesNotExist();
			if (!$user->active)
				throw new UserDeactivatedException();
			$auth = new Authorize($user->user_id, $password);
			$auth->authenticate();
			$token = $auth->token();
			echo json_encode(array('authType'=>Authorize:: $AuthType,'authFields'=>"pinwheel_token=$token"));
		} catch (UserDoesNotExist $e) {
			echo $e->json_encode();
		} catch (UserDeactivatedException $e) {
			echo $e->json_encode();
		}
	}
}