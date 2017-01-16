<?php
	/**
	 * Clase que representa una tabla de las bases de datos. Está relacionada con una clase de tipo DagRegistroActivo.
	 *
	 * Esta clase puede utilizarse de dos modos: primero instanciando una clase heredada (ej: class Libros extends DagTablaActiva { }; $tabla = new Libros();),
	 * o bien inicializando la clase en modo emulado ($libros = new DagTablaActiva('Libros');).
	 * @package ragnajag
	 */
	class DagTablaActiva
	{
		/**
		 * Array que contiene las relaciones de la tabla.
		*/
		public $_relaciones;
		
		/**
		 * Nombre de la tabla representada.
		*/
		public $_nombre_tabla;
		
		/**
		 * Nombre de la clase DagRegistroActivo asociada.
		*/
		public $_nombre_registro;
		
		/**
		 * Nombre de la conexión de la BD donde se encuentra la tabla.
		*/
		public $_nombre_conexion = 'defecto';
		
		/**
		 * Indica cual de los campos de la tabla es la Primary Key. Por defecto 'id'.
		*/
		protected $_pk = "id";
		
		const FILAS_POR_PAGINA = 25;
		
		/**
		 * Constructor. Inicializa la tabla y sus relaciones.
		 * @param string $nombre_emulado Si se especifica, DagTablaActiva actúa como un emulador de la tabla activa con el nombre indicado.
		*/
		public function __construct($nombre_emulado = null)
		{
			// Si no está definido, usar el nombre de la clase.
			if ($nombre_emulado == null) { $nombre_emulado = get_class($this); }
			
			// Si todo y así el nombre no está inicializado, mostrar un error.
			if ($nombre_emulado == "DagTablaActiva") 
			{ 
				throw new Exception('No se puede inicializar DagTablaActiva sin especificar una tabla que representar.'); 
			}
			
			// La clase debe tener un nombre de registro definido.
			if (!$this->_nombre_registro) 
			{ 
				throw new Exception('No se puede inicializar DagTablaActiva sin especificar a que RegistroActivo corresponde.');
			}
			
			// Carga el nombre de la tabla si no existe.
			if (!$this->_nombre_tabla) { $this->_nombre_tabla = claseATabla($nombre_emulado, false); }
		}
		
		/**
		 * Encuentra todos los registros de la tabla.
		 * @param array|string $clauses Si es un string, se aplicará como condición al WHERE de la consulta. Si es una Array, esta se convertirá en las diferentes secuencias especificadas en su interior.
		 * @return array Resultado de la búsqueda.
		*/
		public function encontrarTodos($clauses = array())
		{
			return $this->encontrar($clauses);
		}
		
		/**
		 * Encuentra unos registros concretos.
		 * @param array|string $clauses Si es un string, se aplicará como condición al WHERE de la consulta. Si es una Array, esta se convertirá en las diferentes secuencias especificadas en su interior.
		 * @return array Resultado de la búsqueda.
		*/
		public function encontrar($clauses)
		{
			$sql = "SELECT * FROM " . $this->_nombre_tabla;
			$config = Dag::$c->getConfiguracion($this->_nombre_conexion);
			$sql .= secuenciaSql($clauses, $config->type);
			return $this->encontrarPorSql($sql);
		}
		
		/**
		 * Encuentra unos registros concretos en la página especificada.
		 * @param int $pagina Página actual que retornar. Por defecto 1.
		 * @param array|string $clauses Si es un string, se aplicará como condición al WHERE de la consulta. Si es una Array, esta se convertirá en las diferentes secuencias especificadas en su interior.
		 * @return object Resultado de la búsqueda.
		*/
		public function encontrarPaginados($pagina = 1, $clauses = array())
		{
			if (!is_array($clauses)) { $clauses = array("where" => $clauses); }
			$clauses['limit'] = (self::FILAS_POR_PAGINA * ($pagina - 1)) . ", " . self::FILAS_POR_PAGINA;
			
			return $this->encontrar($clauses);
		}
		
		/**
		 * Devuelve la cantidad de páginas que se necesitarán para paginar el contenido de la tabla delimitado por $clauses.
		 * @param array|string $clauses Si es un string, se aplicará como condición al WHERE de la consulta. Si es una Array, esta se convertirá en las diferentes secuencias especificadas en su interior.
		 * @return int
		*/
		public function contarPaginas($clauses)
		{
			$all = $this->contar($clauses);
			return ceil($all/self::FILAS_POR_PAGINA);
		}
		
		/**
		 * Encuentra un grupo de registros utilizando una consulta SQL.
		 * @param string $sql Sentencia SQL completa.
		 * @return array Resultado de la búsqueda.
		*/
		public function encontrarPorSql($sql)
		{
			// Especifica la clase resultado (DagRegistroActivo) que se generará con el select.
			$clase_resultado = $this->_nombre_registro;
			
			// Carga la clase si existe, si no lanza una excepción
			if ($clase_resultado && class_exists($clase_resultado))
			{
				$class = new ReflectionClass($clase_resultado);
			}
			else
			{
				throw new Exception("El RegistroActivo " . $clase_resultado . " no existe.");
			}
			
			// Con la clase cargada en Reflection, cargamos los datos de la BD.
			$result = array();
			foreach ($this->db()->query($sql) as $row)
			{
				$res = $class->newInstance($row);
				if ($clase_resultado) { $res->_cargado = true; }
				$result[] = $res;
			}
			
			return $result;
		}
		
		/**
		 * Encuentra un registro concreto.
		 * @param array|string $clauses Si es un string, se aplicará como condición al WHERE de la consulta. Si es una Array, esta se convertirá en las diferentes secuencias especificadas en su interior.
		 * @return object Resultado de la búsqueda.
		*/
		public function encontrarUno($clauses)
		{
			if (!is_array($clauses)) { $clauses = array("where" => $clauses); }
			$clauses['limit'] = "1";
			$res = $this->encontrar($clauses);
			return $res[0];
		}
		
		/**
		 * Encuentra un registro concreto utilizando una consulta SQL.
		 * @param string $sql Sentencia SQL completa.
		 * @return DagRegistroActivo Resultado de la búsqueda.
		*/
		public function encontrarUnoPorSql($sql)
		{
			$res = $this->encontrarPorSql($sql);
			return $res[0];
		}
		
		/**
		 * Devuelve una array de relación simple (id => campo) de la tabla.
		 * @param string $campo Nombre del campo del que cargar los valores de la array. Por defecto 'nombre'.
		 * @param array|string $clauses Si es un string, se aplicará como condición al WHERE de la consulta. Si es una Array, esta se convertirá en las diferentes secuencias especificadas en su interior.
		 * @return array Lista resultante.
		*/
		public function listar($campo = 'nombre', $clauses = array())
		{
			$sql = "SELECT " . $this->_pk . ", " . $campo . " FROM " . $this->_nombre_tabla;
			$config = Dag::$c->getConfiguracion($this->_nombre_conexion);
			$sql .= secuenciaSql($clauses, $config->type);
			
			$ret = array();
			foreach ($this->db()->query($sql) as $r)
			{
				$pk = $r[$this->_pk];
				$ret[$pk] = $r[$campo];
			}
			return $ret;
		}
		
		/**
		 * Cuenta los registros de la tabla.
		 * @param array|string $clauses Si es un string, se aplicará como condición al WHERE de la consulta. Si es una Array, esta se convertirá en las diferentes secuencias especificadas en su interior.
		 * @return int Registros contados.
		*/
		public function contar($clauses = "")
		{
			if (is_array($clauses)) { $clauses = $clauses['where']; }
			
			$sql = "SELECT count(*) as c FROM " . $this->_nombre_tabla;
			$config = Dag::$c->getConfiguracion($this->_nombre_conexion);
			$sql .= secuenciaSql($clauses, $config->type);
			
			$stmt = $this->db()->query($sql);
			return $stmt->fetchColumn();
		}
		
		
		/**
		 * Genera una búsqueda personalizada simple de campos=valor, devolviendo uno o varios registros.
		 *
		 * Ejemplo: $this->encontrarPorIdYNombreYSexo(1, "jordi", "m") generará una consulta con "WHERE id = 1 AND nombre ='jordi' AND sexo = 'm'".
		 * Ejemplo2: $this->encontrarUnoPorIdONombre(1, "jordi") generará una consulta "WHERE id=1 OR nombre='jordi' LIMIT 0,1".
		 * @param string $name Nombre de la función que se ha llamado.
		 * @param array $params Parametros enviados.
		 * @return mixed El resultado
		*/
		public function __call($name, $params)
		{
			if (strstr($name, "encontrarUnoPor") == $name)
			{
				$uno = true;
				$campos = str_replace("encontrarUnoPor", "", $name);
			}
			elseif (strstr($name, "encontrarPor") == $name)
			{
				$uno = false;
				$campos = str_replace("encontrarPor", "", $name);
			}
			else
			{
				throw new Exception("DagTablaActiva::__call -> Perdón, no he entendido la función ". $name);
			}
			
			if ($campos == "")
			{
				throw new Exception("DagTablaActiva::__call -> No has especificado campos en ". $name);
			}
			
			$separado = strtolower(preg_replace('/([A-Z])/', '_\\1', struncapitalize($campos)));
			$parts = explode('_', $separado);
			
			if (count($parts) != (count($params) * 2) - 1)
			{
				throw new Exception("DagTablaActiva::__call -> No hay la misma cantidad de campos que de parametros ". $name);
			}
			
			$sql = "SELECT * FROM " . $this->_nombre_tabla . " WHERE ";
			$i = 0;
			foreach ($parts as $p)
			{
				if ($p == "y")
				{
					$sql .= " AND ";
				}
				elseif ($p == "o")
				{
					$sql .= " OR ";
				}
				else
				{
					$sql .= sql('`' . $p . "` = '%'", $params[$i]);
					$i++;
				}
			}
			
			if ($uno)
			{
				$sql .= " LIMIT 0, 1";
				return $this->encontrarUnoPorSql($sql);
			}
			else
			{
				return $this->encontrarPorSql($sql);
			}
		}
		
		/**
		 * Crea una relación de tipo PerteneceA (BelongsTo) a la tabla indicada.
		 * @param string $clase Nombre de la clase (Heredera de DagRegistroActivo) con la que crear la relación.
		 * @param string $nombre_relacion Nombre utilizado para referenciar la relación. Si es null, se utilizará una versión en minusculas del nombre de la clase. Null por defecto.
		 * @param string $clave Nombre de la clave que referencia la relación. Si es null, se utilizará el nombre de la clase  + "_id". Null por defecto.
		 * @param array $sql_clauses Array parseable por secuenciaSql que contiene criterios para la relación. null por defecto.
		*/
		public function perteneceA($clase, $nombre_relacion = null, $clave = null, $sql_clauses = null)
		{
			$this->relacionar(DagRelacion::BELONGS_TO, $clase, $nombre_relacion, $clave, $sql_clauses);
		}
		
		/**
		 * Crea una relación de tipo TieneVarias (HasMany) a la tabla indicada.
		 * @param string $clase Nombre de la clase (Heredera de DagRegistroActivo) con la que crear la relación.
		 * @param string $nombre_relacion Nombre utilizado para referenciar la relación. Si es null, se utilizará una versión en minusculas y en plural del nombre de la clase. Null por defecto.
		 * @param string $clave Nombre de la clave que referencia la relación. Si es null, se utilizará el nombre de la clase  + "_id". Null por defecto.
		 * @param array $sql_clauses Array parseable por secuenciaSql que contiene criterios para la relación. null por defecto.
		*/
		public function tieneVarias($clase, $nombre_relacion = null, $clave = null, $sql_clauses = null) { $this->tieneVarios($clase, $nombre_relacion, $clave, $sql_clauses); }
		
		/**
		 * Crea una relación de tipo PerteneceA (HasMany) a la tabla indicada.
		 * @param string $clase Nombre de la clase (Heredera de DagRegistroActivo) con la que crear la relación.
		 * @param string $nombre_relacion Nombre utilizado para referenciar la relación. Si es null, se utilizará una versión en minusculas y en plural del nombre de la clase. Null por defecto.
		 * @param string $clave Nombre de la clave que referencia la relación. Si es null, se utilizará el nombre de la clase  + "_id". Null por defecto.
		 * @param array $sql_clauses Array parseable por secuenciaSql que contiene criterios para la relación. null por defecto.
		*/
		public function tieneVarios($clase, $nombre_relacion = null, $clave = null, $sql_clauses = null)
		{
			$this->relacionar(DagRelacion::HAS_MANY, $clase, $nombre_relacion, $clave, $sql_clauses);
		}
		
		/**
		 * Crea una relación de tipo TieneUna (HasOne) a la tabla indicada.
		 * @param string $clase Nombre de la clase (Heredera de DagRegistroActivo) con la que crear la relación.
		 * @param string $nombre_relacion Nombre utilizado para referenciar la relación. Si es null, se utilizará una versión en minusculas del nombre de la clase. Null por defecto.
		 * @param string $clave Nombre de la clave que referencia la relación. Si es null, se utilizará el nombre de la clase  + "_id". Null por defecto.
		 * @param array $sql_clauses Array parseable por secuenciaSql que contiene criterios para la relación. null por defecto.
		*/
		public function tieneUna($clase, $nombre_relacion = null, $clave = null, $sql_clauses = null) { $this->tieneUn($clase, $nombre_relacion, $clave, $sql_clauses); }
		
		/**
		 * Crea una relación de tipo TieneUna (HasOne) a la tabla indicada.
		 * @param string $clase Nombre de la clase (Heredera de DagRegistroActivo) con la que crear la relación.
		 * @param string $nombre_relacion Nombre utilizado para referenciar la relación. Si es null, se utilizará una versión en minusculas del nombre de la clase. Null por defecto.
		 * @param string $clave Nombre de la clave que referencia la relación. Si es null, se utilizará el nombre de la clase  + "_id". Null por defecto.
		 * @param array $sql_clauses Array parseable por secuenciaSql que contiene criterios para la relación. null por defecto.
		*/
		public function tieneUn($clase, $nombre_relacion = null, $clave = null, $sql_clauses = null)
		{
			$this->relacionar(DagRelacion::HAS_ONE, $clase, $nombre_relacion, $clave, $sql_clauses);
		}
		
		/**
		 * Crea una relación de tipo TieneYPerteneceAVarias (ManyToMany) a la tabla indicada.
		 * @param string $clase Nombre de la clase (Heredera de DagRegistroActivo) con la que crear la relación.
		 * @param string $nombre_relacion Nombre utilizado para referenciar la relación. Si es null, se utilizará una versión en minusculas y en plural del nombre de la clase. Null por defecto.
		 * @param string $clave Nombre de la clave que referencia la relación. Si es null, se utilizará el nombre de la clase  + "_id". Null por defecto.
		 * @param array $sql_clauses Array parseable por secuenciaSql que contiene criterios para la relación. null por defecto.
		*/
		public function tieneYPerteneceAVarias($clase, $nombre_relacion = null, $clave = null, $sql_clauses = null) { $this->tieneYPerteneceAVarios($clase, $nombre_relacion, $clave, $sql_clauses); }
		
		/**
		 * Crea una relación de tipo TieneYPerteneceAVarias (ManyToMany) a la tabla indicada.
		 * @param string $clase Nombre de la clase (Heredera de DagRegistroActivo) con la que crear la relación.
		 * @param string $nombre_relacion Nombre utilizado para referenciar la relación. Si es null, se utilizará una versión en minusculas y en plural del nombre de la clase. Null por defecto.
		 * @param string $clave Nombre de la clave que referencia la relación. Si es null, se utilizará el nombre de la clase  + "_id". Null por defecto.
		 * @param array $sql_clauses Array parseable por secuenciaSql que contiene criterios para la relación. null por defecto.
		*/
		public function tieneYPerteneceAVarios($clase, $nombre_relacion = null, $clave = null, $sql_clauses = null)
		{
			$this->relacionar(DagRelacion::MANY_TO_MANY, $clase, $nombre_relacion, $clave, $sql_clauses);
		}
		
		private function relacionar($tipo, $clase, $nombre_relacion = null, $clave = null, $sql_clauses = null)
		{
			$r = new DagRelacion($tipo, $clase, $nombre_relacion, $this->_nombre_registro, $clave, $sql_clauses);
			$this->_relaciones[$r->nombre] = &$r;
		}
		
		/**
		 * Devuelve la conexión a la base de datos indicada por $_nombre_conexion.
		 * @return PDO
		*/
		public function db()
		{
			return Dag::$c->{$this->_nombre_conexion};
		}
	}
?>