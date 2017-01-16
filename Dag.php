<?php	
	/**
	 * Librería estática de gestión de datos, basado en DagDagRegistroActivo y DagTablaActiva.
	 * @package ragnajag
	 */
	class Dag
	{
		/**
		 * Objeto encargado de la gestión de las tablas de las Bases de Datos.
		 * @static
		*/
		public static $t;
		
		/**
		 * Objeto encargado de la gestión de las conexiones a las Bases de Datos.
		 * @static
		*/
		public static $c;
		
		/**
		 * Función estática encargada de la inicialización de los objetos de la clase.
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