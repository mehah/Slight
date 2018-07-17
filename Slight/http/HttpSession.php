<?php
namespace Slight\http;

use Slight\Config;
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

	public function hasAttribute($index) {
		return isset($this->attr[$index]);
	}

	public function destroy() {
		unset($_SESSION[Config::getProjectName()]);
	}

	public function setUserPrincipal(UserPrincipal $user) {
		if ($this->userPrincipal != null)
			throw new \RuntimeException("It is not possible to change the user, only when there is no linked session.");
		
		$this->userPrincipal = $user;
	}

	public function getUserPrincipal(): ?UserPrincipal {
		return $this->userPrincipal;
	}

	public static function &getInstance(): self {
		if (PHP_SESSION_NONE === session_status()) {
			session_start();
		}
		
		$projectName = Config::getProjectName();
		if (isset($_SESSION[$projectName])) {
			return $_SESSION[$projectName];
		}
		
		$session = new self();
		
		$_SESSION[$projectName] = &$session;
		
		return $session;
	}
}