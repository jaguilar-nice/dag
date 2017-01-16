<?php
	/**
	 * Clase encargada de la gestión de las tablas de las Bases de Datos.
	 *
	 * Los nombres de las tablas se cargarán de los archivos [seccion]/modelos/tablas/.
	 * @package ragnajag
	 */
	class DagTablas
	{
		private $arr;
		
		/**
		 * Devuelve la tabla especificada.
		 * @param string $name Nombre de la tabla.
		 * @return RagTablaActiva
		 */
		public function __get($name)
		{
			if ($this->arr[$name])
			{
				return $this->arr[$name];
			}
			else
			{
				if (class_exists(ucfirst($name)))
				{
					$clase = new ReflectionClass(ucfirst($name));
					$tabla = $clase->newInstance();
				}
				else
				{
					$tabla = new DagTablaActiva($name);
				}
				
				$this->arr[$name] = &$tabla;
				
				return $tabla;
			}
		}
	}
?>