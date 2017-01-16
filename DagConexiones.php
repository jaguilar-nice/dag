<?php
	/**
	 * Esta clase gestiona un conjunto de conexiones de tipo PDO con las bases de datos.
	 *
	 * Carga los datos de /config/databases.xml y se conecta de forma dinámica cuando son requeridos.
	 * @package ragnajag 
	 */
	class DagConexiones
	{
		private $links;
		
		private $configs;
		
		/**
		 * Devuelve la conexión especificada.
		 *
		 * Si el nombre de la DB se 'defecto'. Se cargará la DB indicada en 'server.xml'.
		 * @param string $name Nombre de la conexión. Tal como se indica en 'databases.xml'.
		 * @return PDO
		*/
		public function __get($name)
		{
			if ($name == 'defecto') { $name = Rag::$info->server->DBPorDefecto; }
			
			if (!$this->links[$name])
			{
				if (Rag::$info->databases->$name)
				{
					$config = $this->getConfiguracion($name);
					
					if ($config->type== "mysql")
					{
						$this->links[$name] = new PDO($config->dsn, $config->user, $config->password, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
					}
					else
					{
						$this->links[$name] = new PDO($config->dsn, $config->user, $config->password);
					}
				}
				else
				{
					user_error('No se ha encontrado los datos de conexión a la base de datos de "' . $name . '".');
				}
			}
			
			return $this->links[$name];
		}
		
		/**
		 * Devuelve la configuración de la conexión especificada.
		 *
		 * Si el nombre de la DB se 'defecto'. Se cargará la DB indicada en 'server.xml'.
		 * @param string $name Nombre de la conexión. Tal como se indica en 'databases.xml'.
		 * @return object
		*/
		public function getConfiguracion($name)
		{
			if ($name == 'defecto') { $name = Rag::$info->server->DBPorDefecto; }
			
			return Rag::$info->databases->$name;
		}
	}
?>