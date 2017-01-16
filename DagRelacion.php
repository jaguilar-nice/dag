<?php
	/**
	 * Esta clase representa la relación entre tablas activas. Cada instancia conforma una relación unilateral, de una clase base a una relacionada.
	 * @package ragnajag
	 */
	class DagRelacion
	{
		const HAS_MANY = 1;
		const HAS_ONE = 2;
		const BELONGS_TO = 3;
		const MANY_TO_MANY = 4;
		
		/**
		 * Tipo de relación.
		*/
		public $tipo;
		
		/**
		 * Nombre de la relación.
		*/
		public $nombre;
		
		/**
		 * Nombre de la clase relacionada.
		*/
		public $clase = null;
		
		/**
		 * Clave de la clase base que apunta a la PK de la clase relacionada.
		*/
		public $clave = null;
		
		/**
		 * Clave de la clase relacionada que apunta a la PK de la clase base. Sólo en relaciones bidireccionales.
		*/
		public $clave2 = null;
		
		/**
		 * Nombre de la tabla que relaciona ambas clases. Solo en casos TieneYPerteneceAVarios.
		*/
		public $tabla = null;
		
		/**
		 * Condiciones SQL para crear la relación.
		*/
		public $sql_clauses = "";
		
		/**
		 * Constructor. Inicializa la relación.
		 * @param int $tipo_relacion.
		 * @param string $clase Nombre de la clase relacionada.
		 * @param string $nombre_relacion
		 * @param string $padre Nombre de la clase base
		 * @param mixed $clave.
		 * @param string $sql_clauses
		*/
		function __construct($tipo_relacion, $clase, $nombre_relacion, $padre, $clave, $sql_clauses)
		{
			$this->tipo = $tipo_relacion;
			$this->clase = $clase;
			
			switch($tipo_relacion)
			{
				case self::BELONGS_TO:
					$this->nombre = ($nombre_relacion) ? $nombre_relacion : strtolower($clase);
					$this->clave = ($clave) ? $clave : strtolower($this->nombre) . "_id";
					break;
				
				case self::HAS_MANY:
					$this->clave = ($clave) ? $clave : strtolower($padre) . "_id";
					$this->nombre = ($nombre_relacion) ? $nombre_relacion : struncapitalize(pluralizar($clase));
					break;
				
				case self::HAS_ONE:
					$this->clave = ($clave) ? $clave : strtolower($padre) . "_id";
					$this->nombre = ($nombre_relacion) ? $nombre_relacion : strtolower($clase);
					break;
				
				case self::MANY_TO_MANY:
					$this->clave = strtolower($padre) . "_id";
					$this->clave2 = strtolower($clase) . "_id";
					$this->nombre = ($nombre_relacion) ? $nombre_relacion : struncapitalize(pluralizar($clase));
					
					$arr = array(claseATabla($padre), claseATabla($clase));
					sort($arr, SORT_STRING);
					$this->tabla = $arr[0] . "_" . $arr[1];
					break;
			}
			
			if ($sql_clauses != null)
			{
				if ($sql_clauses['where'])
				{
					$where = "AND " . $sql_clauses['where'];
					$sql_clauses['where'] = null;					
				}
				
				$this->sql_clauses .= $where . secuenciaSql($sql_clauses);
			}
		}
	}
?>