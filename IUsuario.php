<?php
	/**
	 * Interfaz que debe implementar la clase que representa el registro de usuario de la aplicación. Necesaria para utilizar RagLogin.
	 * @package ragnajag
	 */
	interface iUsuario
	{
		/**
		 * Esta función devolverá el valor de login del objeto de usuario.
		 * @return string
		*/
		public function getLogin();
		
		/**
		 * Esta función devolverá el password del objeto de usuario.
		 * @return string
		*/
		public function getPassword();
	}

?>