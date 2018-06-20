<?php
namespace fw\http;

use fw\Project;
use fw\UserPrincipal;

final class HttpSession {

	private $userPrincipal;

	private $attr = Array();

	public function getAttribute($index) {
		return $this->attr[$index] ?? null;
	}

	public function setAttribute($index, $value) {
		$this->attr[$index] = $value;
	}

	public function destroy() {
		unset($_SESSION[Project::$name]);
	}

	public function setUserPrincipal(UserPrincipal $user) {
		if ($this->userPrincipal != null)
			throw new \RuntimeException("It is not possible to change the user, only when there is no linked session.");
		
		$this->userPrincipal = $user;
	}

	public function getUserPrincipal(): ?UserPrincipal {
		return $this->userPrincipal;
	}
}