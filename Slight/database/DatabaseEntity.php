<?php
namespace Slight\database;

final class DatabaseEntity {

	public static function all(string $className, int $qntPerPage = null, int $page = null, array $orderBy = null): iterable {
		return self::search($className, null, null, $qntPerPage, $page, $orderBy);
	}

	public static function search(string $className, array $fieldsName = null, array $whereFields = null, int $qntPerPage = null, int $page = null, array $orderBy = null) {
		$conn = DatabaseConnection::getInstance();
		
		$tableName = $className::$table;
		
		$whereStr = ' WHERE 1';
		if ($whereFields) {
			foreach ($whereFields as $field => $value) {
				$whereStr .= ' AND ' . $tableName . '.' . $field . ' = ?';
				$whereValues[] = $value;
			}
		}
		
		$limit = '';
		if ($qntPerPage !== null && $page !== null) {
			$start = ($page * $qntPerPage) - $qntPerPage;
			$limit = ' LIMIT ' . $start . ',' . $qntPerPage;
		}
		
		$stmt = $conn->{$whereFields ? 'prepare' : 'query'}('SELECT ' . ($fieldsName ? implode(',', $fieldsName) : '*') . ' FROM ' . $tableName . $whereStr . ($orderBy ? ' ORDER BY '.implode(',', $orderBy) : '') . $limit);
		
		if ($whereFields) {
			$i = 0;
			foreach ($whereValues as $value) {
				$stmt->bindValue(++ $i, $value);
			}
		}
		
		if ($stmt->execute()) {
			$list = [];
			while ($entityDB = $stmt->fetchObject($className)) {
				$list[] = $entityDB;
			}
			
			return $list;
		}
	}

	public static function find(string $className, $key): ?Entity {
		$entity = new $className();
		$entity->{$entity::$primaryKey} = $key;
		
		return self::load($entity) ? $entity : null;
	}

	public static function findBy(string $className, array $whereFields): ?Entity {
		return self::load($entity = new $className(), $whereFields) ? $entity : null;
	}

	public static function load(Entity $entity, array $whereFields = ['$$id'=>0]): bool {
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
		
		$whereStr = '1';
		$whereValues = [];
		if (isset($whereFields['$$id'])) {
			$whereStr .= ' AND ' . $tableName . '.' . $primaryKey . ' = ?';
			$whereValues[] = $entity->{$primaryKey};
		} else {
			foreach ($whereFields as $field => $value) {
				$whereStr .= ' AND ' . $tableName . '.' . $field . ' = ?';
				$whereValues[] = $value;
			}
		}
		
		$stmt = $conn->prepare('SELECT * FROM `' . $tableName . '`' . $relString . ' WHERE ' . $whereStr);
		
		$i = 0;
		foreach ($whereValues as $value) {
			$stmt->bindValue(++ $i, $value);
		}
		
		if (! $stmt->execute() || $stmt->rowCount() == 0) {
			return false;
		}
		
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
		
		return true;
	}

	public static function insert(Entity $entity): bool {
		$conn = DatabaseConnection::getInstance();
		
		$relationship = $entity::$relationship ?? null;
		
		$values = [];
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

	public static function update(Entity $entity, array $fields = null): bool {
		$conn = DatabaseConnection::getInstance();
		
		$primaryKey = $entity::$primaryKey;
		
		$tableName = $entity::$table;
		$relationship = $entity::$relationship ?? null;
		
		$values = [];
		$fieldsStr = null;
		if ($fields) {
			foreach ($fields as $name) {
				if ($fieldsStr) {
					$fieldsStr .= ',';
				}
				
				$value = $entity->{$name};
				if ($value && $relationship && $r = $relationship[$name] ?? null) {
					$name = $r;
					$value = $value->{$value::$primaryKey};
				}
				
				$fieldsStr .= '`' . $name . '`=?';
				
				$values[] = $value;
			}
		} else {
			$props = (new \ReflectionClass($entity))->getProperties();
			foreach ($props as $prop) {
				$name = $prop->getName();
				if ($prop->isStatic() || $name === 'class' || $name === $primaryKey) {
					continue;
				}
				
				if ($fieldsStr) {
					$fieldsStr .= ',';
				}
				
				$value = $prop->getValue($entity);
				if ($value && $relationship && $r = $relationship[$name] ?? null) {
					$name = $r;
					$value = $value->{$value::$primaryKey};
				}
				
				$fieldsStr .= '`' . $name . '`=?';
				
				$values[] = $value;
			}
		}
		
		$stmt = $conn->prepare('UPDATE `' . $tableName . '` SET ' . $fieldsStr . ' WHERE `' . $primaryKey . '` = ?');
		
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
		if (empty($fieldsFilter)) {
			throw new \Exception("Filter fields have not been defined.");
		}
		
		$filter = '';
		foreach ($fieldsFilter as $key => $value) {
			$filter .= ' AND ' . $key . (is_array($value) ? ' in (' . implode(',', $value) . ')' : '=' . (is_string($value) ? '\'' . $value . '\'' : $value));
		}
		
		$conn = DatabaseConnection::getInstance();
		return $conn->exec('DELETE FROM ' . $className::$table . ' WHERE 1' . $filter) > 0;
	}
}