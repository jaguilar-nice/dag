<?php	
	/**
	 * Librer�a est�tica de gesti�n de datos, basado en DagDagRegistroActivo y DagTablaActiva.
	 * @package ragnajag
	 */
	class Dag
	{
		/**
		 * Objeto encargado de la gesti�n de las tablas de las Bases de Datos.
		 * @static
		*/
		public static $t;
		
		/**
		 * Objeto encargado de la gesti�n de las conexiones a las Bases de Datos.
		 * @static
		*/
		public static $c;
		
		/**
		 * Funci�n est�tica encargada de la inicializaci�n de los objetos de la clase.
		 * @static
		*/
		public static function cargar()
		{
			//self::startDoctrine();
			
			self::$t = new DagTablas();
			self::$c = new DagConexiones();
		}
		
		private static function startDoctrine()
		{
			/*require_once '../ragnajag/clases/vendor/Doctrine/Common/ClassLoader.php';

			$classLoader = new \Doctrine\Common\ClassLoader('Doctrine', '../ragnajag/clases/vendor/');
			$classLoader->register();*/
		}
	}
?>