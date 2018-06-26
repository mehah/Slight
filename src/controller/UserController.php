<?php
namespace src\controller;

use Slight\ComponentController;
use src\model\User;

class UserController extends ComponentController {

	public function init() {
		$this->getSession()->destroy();
		
		return "Hello World!";
	}

	public function update($id, $name) {
		return "User id($id) updated to name: $name";
	}

	public function insert(User $user) {
		$msg;
		if ($this->validate($user)->hasError()) {
			$msg = 'Name is required.';
		} else {
			try {
				$msg = $user->insert() ? 'User inserted.' : 'Error on insert User.';
			} catch (\Exception $e) {
				$this->status(500);
				$msg = $e->getMessage();
			}
		}
		
		return $msg;
	}

	public function putOnSession(User $user) {
		$this->getSession()->setUserPrincipal($user);
		
		return "User inserted on session.";
	}
}