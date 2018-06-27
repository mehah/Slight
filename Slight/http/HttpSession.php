<?php
namespace Slight\http;

use Slight\Project;
use Slight\UserPrincipal;

final class HttpSession {

	private $userPrincipal;

	private $attr = [];

	public function getAttribute($index) {
		return $this->attr[$index] ?? null;
	}

	public function setAttribute($index, $value) {
		$this->attr[$index] = $value;
	}

	public function destroy() {
		unset($_SESSION[Project::getName()]);
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