<?php
namespace fw\database;

final class DatabaseEntity {

	public static function all(string $className): iterable {
		$conn = DatabaseConnection::getInstance();
		
		$stmt = $conn->query('SELECT * FROM ' . $className::$table);
		
		$res = $stmt->execute();
		
		if ($res) {
			$list = Array();
			while ($entityDB = $stmt->fetchObject($className)) {
				$list[] = $entityDB;
			}
			
			return $list;
		}
	}

	public static function find(string $className, $key): Entity {
		$entity = new $className();
		$entity->{$entity::$primaryKey} = $key;
		
		return self::load($entity) ? $entity : null;
	}

	public static function load(Entity $entity): bool {
		$conn = DatabaseConnection::getInstance();
		$conn->setAttribute(\PDO::ATTR_FETCH_TABLE_NAMES, true);
		
		$tableName = $entity::$table;
		
		$relString = '';
		if ($relationship = $entity::$relationship ?? null) {
			foreach ($relationship as $propName => $fieldName) {
				$rel = $entity->{$propName};
				$tableNameRel = $rel::$table;
				$relString .= ' LEFT JOIN ' . $tableNameRel . ' ON ' . $tableName . '.' . $fieldName . '=' . $tableNameRel . '.' . $rel::$primaryKey;
			}
		}
		
		$primaryKey = $entity::$primaryKey;
		$stmt = $conn->query('SELECT * FROM `' . $tableName . '`' . $relString . ' WHERE ' . $tableName . '.' . $primaryKey . ' = ' . $entity->{$primaryKey});
		
		$res = $stmt->execute();
		
		if ($res) {
			$entityDB = $stmt->fetch(\PDO::FETCH_ASSOC);
			
			$props = (new \ReflectionClass($entity))->getProperties();
			foreach ($props as $prop) {
				$name = $prop->getName();
				if ($prop->isStatic() || $name === 'class') {
					continue;
				}
				
				if ($relationship && $rel = $relationship[$name] ?? null) {
					$entityRel = $entity->{$name};
					
					$tableNameRel = $entityRel::$table;
					
					$propsRel = (new \ReflectionClass($entityRel))->getProperties();
					foreach ($propsRel as $propRel) {
						$nameRel = $propRel->getName();
						if ($propRel->isStatic() || $nameRel === 'class') {
							continue;
						}
						
						$entityRel->{$nameRel} = $entityDB[$tableNameRel . '.' . $nameRel];
					}
				} else {
					$entity->{$name} = $entityDB[$tableName . '.' . $name];
				}
			}
			
			return $res;
		}
	}

	public static function insert(Entity $entity): bool {
		$conn = DatabaseConnection::getInstance();
		
		$relationship = $entity::$relationship ?? null;
		
		$values = Array();
		$fields = null;
		$params = null;
		$props = (new \ReflectionClass($entity))->getProperties();
		foreach ($props as $prop) {
			$name = $prop->getName();
			if ($prop->isStatic() || $name === 'class') {
				continue;
			}
			
			if ($fields) {
				$fields .= ',';
				$params .= ',';
			}
			
			$value = $prop->getValue($entity);
			if ($value && $relationship && $r = $relationship[$name] ?? null) {
				$name = $r;
				$value = $value->{$value::$primaryKey};
			}
			
			$fields .= '`' . $name . '`';
			$params .= '?';
			
			$values[] = $value;
		}
		
		$stmt = $conn->prepare('INSERT INTO `' . $entity::$table . '`(' . $fields . ') VALUES (' . $params . ')');
		
		$i = 0;
		foreach ($values as $value) {
			$stmt->bindValue(++ $i, $value);
		}
		
		return $stmt->execute();
	}

	public static function update(Entity $entity): bool {
		$conn = DatabaseConnection::getInstance();
		
		$primaryKey = $entity::$primaryKey;
		
		$tableName = $entity::$table;
		$relationship = $entity::$relationship ?? null;
		
		$values = Array();
		$fields = null;
		$params = null;
		$props = (new \ReflectionClass($entity))->getProperties();
		foreach ($props as $prop) {
			$name = $prop->getName();
			if ($prop->isStatic() || $name === 'class' || $name === $primaryKey) {
				continue;
			}
			
			if ($fields) {
				$fields .= ',';
			}
			
			$value = $prop->getValue($entity);
			if ($value && $relationship && $r = $relationship[$name] ?? null) {
				$name = $r;
				$value = $value->{$value::$primaryKey};
			}
			
			$fields .= '`' . $name . '`=?';
			
			$values[] = $value;
		}
		
		$stmt = $conn->prepare('UPDATE `' . $tableName . '` SET ' . $fields . ' WHERE `' . $primaryKey . '` = ?');
		
		$i = 0;
		foreach ($values as $value) {
			$stmt->bindValue(++ $i, $value);
		}
		
		$stmt->bindValue(++ $i, $entity->{$primaryKey});
		
		return $stmt->execute();
	}

	public static function delete(Entity $entity): bool {
		$conn = DatabaseConnection::getInstance();
		
		$primaryKey = $entity::$primaryKey;
		
		$stmt = $conn->query('DELETE FROM `' . $entity::$table . '` WHERE `' . $primaryKey . '` = ' . $entity->{$primaryKey});
		
		return $stmt->execute();
	}

	public static function deleteWithFilter(string $className, Array $fieldsFilter): bool {
		if (! $fieldsFilter || count($fieldsFilter) === 0) {
			throw new \Exception("Não foi definido os campos para filtro.");
		}
		
		$filter = '';
		foreach ($fieldsFilter as $key => $value) {
			$filter .= ' AND ' . $key . (is_array($value) ? ' in (' . implode(',', $value) . ')' : '=' . (is_string($value) ? '\'' . $value . '\'' : $value));
		}
		
		$conn = DatabaseConnection::getInstance();
		return $conn->exec('DELETE FROM ' . $className::$table . ' WHERE 1' . $filter) > 0;
	}
}
