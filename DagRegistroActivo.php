<?php
	/**
	 * Clase que representa un registro de tabla de las bases de datos. Está relacionada con una clase de tipo DagTablaActiva.
	 * @package ragnajag
	 */
	class DagRegistroActivo extends RagnaHash
	{	
		private $_nuevo = true;
		private $_relaciones = array();
		private $_id;
		private $_sqlLanzado = false;
		
		/**
		 * Nombre de la tabla representada.
		*/
		public $_tabla = "";
		
		/**
		 * Nombre de la clase DagTablaActiva asociada.
		*/
		public $_clase_tabla;
		
		/**
		 * Nombre de la clase
		*/
		protected $_clase = null;
		
		/**
		 * Indica cual de los campos de la tabla es la Primary Key. Por defecto 'id'.
		*/
		protected $_pk = "id";
		
		/**
		 * Sentencias SQL para la carga de la función.
		*/
		protected $_sql = "";
		
		/**
		 * Indica si el registro se ha cargado a memoria.
		*/
		public $_cargado = false;
		
		/**
		 * Constructor.
		 * @param mixed $param1 Si el primer número es un número, cargará el registro cuya PK coincida con ese número. Si es una array, cargará los valores indicados en ella.
		 * @param mixed $param2 Indica el nombre de la clase.
		 * @param mixed $param3 Indica el nombre de la clase.
		*/
		public function __construct()
		{
			$num_args = func_num_args();
			if ($num_args >= 1)
			{
				$arg = func_get_arg(0);
				if (is_array($arg))
				{
					if (count($arg) == 1 && $arg[$this->_pk])
					{
						$this->getPorId($arg[$this->_pk]);
					}
					else
					{
						foreach ($arg as $k => $v)
						{
							$this->set($k, $v);
						}
					}
				}
				else
				{
					$this->getPorId($arg);
				}
				
				if ($num_args >= 2)
				{
					$this->_clase = func_get_arg(1);
				}
				
				if (!$this->_clase_tabla) 
				{
					$this->_clase_tabla = pluralizar($this->getClase());
				}
			}
		}
		
		private function getPorId($id)
		{
			$this->_id = $id;
		}
		
		/**
		 *	@deprecated
		 */
		public function unido($relacion, $id)
		{
			foreach ($this->_relaciones as $relacion)
			{
				if ($relacion->{$this->_pk} == $id)
				{
					return true;
				}
			}
			return false;
		}
		
		/**
		 * @deprecated
		 */
		public function unir($relacion, $objeto, $relacion2 = false)
		{
			$rel = $this->getRelacion($relacion);
			
			if(is_string($objeto))
			{
				$clase = new ReflectionClass($rel->clase);
				$objeto = $clase->newInstance($objeto);
			}
			
			switch($rel->tipo)
			{
				case DagRelacion::BELONGS_TO:
					if ($this->{$rel->clave} != $objeto->{$this->_pk})
					{
						$this->{$rel->clave} = $objeto->{$this->_pk};
						$this->_relaciones[$relacion] = &$objeto;
						$this->guardar();
					}
					break;
				
				case DagRelacion::HAS_MANY:
					if ($objeto->{$rel->clave} != $this->{$this->_pk})
					{
						$this->_relaciones[$relacion]->addValor(&$objeto);
					}
					break;
				
				case DagRelacion::HAS_ONE:
					if ($objeto->{$rel->clave} != $this->{$this->_pk})
					{
						$this->_relaciones[$relacion] = &$objeto;
					}
					break;
				
				case DagRelacion::MANY_TO_MANY:				
					if($this->db()->count($rel->tabla, $rel->clave . "='" . $this->get($this->_pk) . "' AND " . $rel->clave2 . "='" . $objeto->get($objeto->_pk)  . "'") == 0)
					{
						$this->db()->query("INSERT INTO " . $rel->tabla . " (" . $rel->clave . ", " . $rel->clave2 . ") VALUES ('" . $this->__get($this->_pk) . "', '" . $objeto->__get($objeto->_pk) . "');" );
						if ($this->_relaciones[$relacion] == null)
						{
							$this->_relaciones[$relacion] = new RagnaHash();
						}
						$this->_relaciones[$relacion]->addValor(&$objeto);
						
						if ($relacion2)
						{
							if ($objeto->_relaciones[$relacion2] == null)
							{
								$objeto->_relaciones[$relacion2] = new RagnaHash();
							}
							$objeto->_relaciones[$relacion2]->addValor(&$this);
							$relacion2 = false;
						}
					}
					break;
			}
			
			if($relacion2) { $objeto->unir($relacion2, $this); }
		}
		
		/**
		 * @deprecated
		 */
		public function desunir($relacion, $objeto, $relacion2 = false)
		{
			$rel = $this->getRelacion("relacion");
			
			if(is_string($objeto))
			{
				$clase = new ReflectionClass($rel->clase);
				$objeto = $clase->newInstance($objeto);
			}
			
			switch($rel->tipo)
			{
				case DagRelacion::BELONGS_TO:
					$this->{$rel->clave} = null;
					unset($this->_relaciones[$relacion]);
					$this->guardar();
					break;
				
				case DagRelacion::HAS_MANY:
					break;
				
				case DagRelacion::HAS_ONE:
					unset($this->_relaciones[$relacion]);
					break;
				
				case DagRelacion::MANY_TO_MANY:
					$this->db()->query("DELETE FROM " . $rel->tabla . " WHERE " . $rel->clave . "='" . $this->__get($this->_pk) . "' AND " . $rel->clave2 . "='" . $objeto->__get($objeto->_pk)  . "'");
					break;
			}
			
			if($relacion2) { $objeto->desunir($relacion2, $this); }
		}
		
		public function getTabla()
		{
			if (!$this->_tabla) 
			{ 
				if (Dag::$t->{pluralizar($this->getClase())}->_tabla)
				{
					$this->_tabla = Dag::$t->{pluralizar($this->getClase())}->_tabla; 
				}
				else
				{
					$this->_tabla = claseATabla($this->getClase()); 
				}
			}
			return $this->_tabla;
		}
		
		/**
		 * Devuelve la clase actual.
		 * @return class
		 */
		public function getClase()
		{
			if (!$this->_clase) 
			{
				$this->_clase = get_class($this);
			}
			return $this->_clase;
		}
		
		/**
		 * Devuelve las relaciones actuales.
		 * @return array
		 */
		public function getRelaciones()
		{
			return Dag::$t->{pluralizar($this->getClase())}->_relaciones;
		}
		
		/**
		 * Devuelve la relación solicitada.
		 * @param string $relacion Nombre de la relación.
		 * @return DagRelacion
		 */
		public function getRelacion($relacion)
		{
			return Dag::$t->{pluralizar($this->getClase())}->_relaciones[$relacion];
		}
		
		private function cargar_datos()
		{
			//Datos
			if ($this->_cargado == false)
			{
				$this->_cargado = true;
				
				if (!$this->_sqlLanzado && $this->_id)
				{
					$sql = sql("SELECT * FROM % WHERE %='%' %", $this->getTabla(), $this->_pk, $this->_id, $this->_sql);
					$valores = $this->db()->fetch($this->db()->query($sql));
					$this->editarValores($valores);
					$this->_sqlLanzado = true;
					$this->_nuevo = false;
				}
				
				//Relaciones
				$arr = $this->getRelaciones();
				if(is_array($arr))
				{
					foreach ($arr as $rel)
					{
						$this->cargar_relacion($rel);
					}
				}
			}
		}
		
		private function cargar_relacion($rel)
		{
			switch($rel->tipo)
			{				
				case DagRelacion::BELONGS_TO:
					$valor = $this->get($rel->clave);
					if ($valor)
					{
						if (class_exists($rel->clase))
						{
							$clase = new ReflectionClass($rel->clase);
							$this->_relaciones[$rel->nombre] = $clase->newInstance($valor);
						}
						else
						{
							$r = new DagRegistroActivo($valor, $rel->clase);
							
							$this->_relaciones[$rel->nombre] = &$r;
						}
						
						$this->_relaciones[$rel->nombre]->_sql .= " " . $rel->sql_clauses;
					}
					else
					{
						$this->_relaciones[$rel->nombre] = new DagRegistroActivo();
					}
					break;
					
				
				case DagRelacion::HAS_MANY:
					$sql = "SELECT " . $this->_pk . " FROM " . claseATabla($rel->clase);
					
					if ($rel->sql_clauses != "" && strstr($rel->sql_clauses, " WHERE") == $rel->sql_clauses)
					{
						$sql .= $rel->sql_clauses;
					}
					else
					{
						$sql .= " WHERE " . $rel->clave . "='" . $this->get($this->_pk) . "' " . $rel->sql_clauses;
					}
					
					$this->db()->query($sql);
					if (class_exists($rel->clase))
					{
						$clase = new ReflectionClass($rel->clase);
					}
					else
					{
						$clase = new ReflectionClass("DagRegistroActivo");
					}
					
					$tmp = array();
					while ($r = $this->db()->fetch())
					{
						if ($r['id'])
						{
							$obj = $clase->newInstance($r['id'], $rel->clase);
							
							$tmp[] = $obj;
						}
						else
						{
							$obj = new DagRegistroActivo();
						}
					}
					
					$this->_relaciones[$rel->nombre] = new RagnaHash();
					$this->_relaciones[$rel->nombre]->setValores($tmp);
					break;
				
				
				case DagRelacion::HAS_ONE:						
					$sql = "SELECT " . $this->_pk . " FROM " . claseATabla($rel->clase);
					
					if ($rel->sql_clauses != "" && strstr($rel->sql_clauses, " WHERE") == $rel->sql_clauses)
					{
						$sql .= $rel->sql_clauses;
					}
					else
					{
						$sql .= " WHERE `" . $rel->clave . "`='" . $this->get($this->_pk) . "' " . $rel->sql_clauses;
					}
					
					$this->db()->query($sql);
					$r = $this->db()->fetch();
					
					if ($r["id"])
					{
						if (class_exists($rel->clase))
						{
							$clase = new ReflectionClass($rel->clase);
							$this->_relaciones[$rel->nombre] = $clase->newInstance($r["id"]);
						}
						else
						{
							$r = new DagRegistroActivo($r["id"], $rel->clase);
							
							$this->_relaciones[$rel->nombre] = &$r;
						}
						
						$this->_relaciones[$rel->nombre]->_sql .= " " . $rel->sql_clauses;
					}
					else
					{
						$this->_relaciones[$rel->nombre] = new DagRegistroActivo();
					}
					break;
				
				
				case DagRelacion::MANY_TO_MANY:
					$sql = "SELECT * FROM " . $rel->tabla;
					
					if ($rel->sql_clauses != "" && strstr($rel->sql_clauses, " WHERE") == $rel->sql_clauses)
					{
						$sql .= $rel->sql_clauses;
					}
					else
					{
						$sql .= " WHERE `" . $rel->clave . "`='" . $this->get($this->_pk) . "' " . $rel->sql_clauses;
					}
					
					$this->db()->query($sql);
					if (class_exists($rel->clase))
					{
						$clase = new ReflectionClass($rel->clase);
					}
					else
					{
						$clase = new ReflectionClass("DagRegistroActivo");
					}
					
					$tmp = array();
					
					while ($r = $this->db()->fetch())
					{
						$obj = $clase->newInstance($r[$rel->clave2], $rel->clase);
						foreach ($r as $k => $v)
						{
							$obj->set($k, $v);
						}
						$tmp[] = $obj;
					}
					
					$this->_relaciones[$rel->nombre] = new RagnaHash();
					$this->_relaciones[$rel->nombre]->setValores($tmp);
					break;
					
			}
				
		}
		
		/**
		 * Inserta el registro en la base de datos.
		 * @return bool True si se efectúa con éxito.
		 */
		public function insertar()
		{
			$this->_nuevo = true;
			return $this->guardar();
		}
		
		/**
		 * Guarda el registro en la base de datos.
		 * @return bool True si se efectúa con éxito.
		 */
		public function guardar()
		{
			$valores = $this->getValores();
			unset($valores[$this->_pk]);
			
			if ($this->_nuevo)
			{
				$this->_nuevo = false;
				return $this->db()->insert($this->getTabla(), $valores);
			}
			else
			{
				return $this->db()->update($this->getTabla(), $this->_pk, $this->__get($this->_pk), $valores);
			}
		}
		
		/**
		 * Destruye el registro de la base de datos.
		 * @param bool $cascada Si es true, destruye todos los objetos que pertenezcan al registro actual, relacionados por HasMany y HasOne. False por defecto.
		 * @param bool $limpiar_referencias Si es true, elimina los registros de relación en relaciones TieneYPerteneceAVarios. True por defecto.
		 */
		public function destruir($cascada = false, $limpiar_referencias = true)
		{
			if ($this->_cargado == false) { $this->cargar_datos(); }
			$arr = $this->getRelaciones();
			
			if(is_array($arr) && ($cascada || $limpiar_referencias))
			{
				foreach ($arr as $rel)
				{
					switch($rel->tipo)
					{								
						case DagRelacion::BELONGS_TO:
							break;
						
						case DagRelacion::HAS_MANY:
							if ($cascada)
							{
								$this->db()->delete(claseATabla($rel->clase), $rel->clave, $this->__get($this->_pk));
							}
							break;
						
						case DagRelacion::HAS_ONE:
							if ($cascada)
							{
								$this->db()->delete(claseATabla($rel->clase), $rel->clave, $this->__get($this->_pk));
							}
							break;
						
						case DagRelacion::MANY_TO_MANY:
							if ($limpiar_referencias)
							{
								$this->db()->delete($rel->tabla, $rel->clave, $this->__get($this->_pk));
							}
							break;
					}
				}
			}
			
			return $this->db()->delete($this->getTabla(), $this->_pk, $this->__get($this->_pk));
		}
		
		/**
		 *	Muestra información representativa del valor del registro de la tabla, normalmente la PK.
		 *	@return string 
		 */
		public function label()
		{
			return $this->__get($this->_pk);
		}
		
		/**
		 *	Muestra información representativa del valor del registro de la tabla, normalmente la PK.
		 *	@return string 
		 */
		public function __toString()
		{
			return $this->__get($this->_pk);
		}
		
		/**
		 *	Devuelve el valor de la PK guardada.
		 *	@return int
		 */
		public function getId()
		{
			return $this->_id;
		}
		
		/**
		 *	Función mágica que permite acceder, primero al valor de un campo del objeto y en segundo a sus relaciones.
		 *	@return mixed
		 */
		public function __get($nombre)
		{
			if ($this->_cargado == false) { $this->cargar_datos(); }
			if (is_array($this->getValores())) 
			{ 
				$vars = array_merge($this->getValores(), $this->_relaciones); 
			}
			else
			{
				$vars = $this->_relaciones;
			}
			
			return $vars[$this->_prefix.$nombre];
		}
		
		
		/**
		 *	Función mágica que permite modificar o insertar el valor de un campo.
		 *	@return mixed
		 */
		public function __set($nombre, $valor)
		{
			if ($this->_cargado == false) { $this->cargar_datos(); }
			
			$this->set($nombre, $valor);
		}

		/**
		 * Devuelve la array de valores dentro del registro.
		 * @return array
		*/
		public function getValores()
		{
			if ($this->_cargado == false) { $this->cargar_datos(); }
			
			return parent::getValores();
		}
		
		/**
		 * Sustituye la array de valores del registro por la indicada.
		 * @param array $array
		*/
		public function setValores($array)
		{
			if ($this->_cargado == false) { $this->cargar_datos(); }
			
			return parent::setValores($array);
		}
		
		/**
		 * Edita o inserta los valores coincidentes en la array, pero sin alterar los valores que ya estuvieran en el registro y no deban editarse.
		 * @param array $array
		*/
		public function editarValores($array)
		{
			if ($this->_cargado == false) { $this->cargar_datos(); }
			
			return parent::editarValores($array);
		}
		
		/**
		 * Añade los valores al registro.
		 * @param array $array
		*/
		public function addValores($array)
		{
			if ($this->_cargado == false) { $this->cargar_datos(); }
			
			return parent::addValores($array);
		}
		
		/**
		 * Devuelve la conexión a la base de datos donde se encuentra la clase DagTablaActiva asociada.
		 * @return DagConexion
		*/
		public function db()
		{
			return Dag::$t->{$this->_clase_tabla}->db();
		}
	}
?>