<?php
	/**
	 * Interfaz que debe implementar la tabla que contiene los usuarios de la aplicación. Necesaria para utilizar RagLogin.
	 * @package ragnajag
	 */
	interface iUsuarios
	{
		/**
		 * Esta función devolverá un item de la tabla con los datos indicados.
		 * @param string $login Nombre (login) del usuario.
		 * @param string $password Password del usuario, tal y como esté en la base de datos (MD5, SHA1...).
		 * @return IUsuario
		*/
		public function encontrarUnoConLogin($login, $password);
	}

?>