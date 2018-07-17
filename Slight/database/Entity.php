<?php
namespace Slight\database;

abstract class Entity {

	public function load(): bool {
		return DatabaseEntity::load($this);
	}

	public function insert(): bool {
		return DatabaseEntity::insert($this);
	}

	public function update(array $fields = null): bool {
		return DatabaseEntity::update($this, $fields);
	}

	public function delete(): bool {
		return DatabaseEntity::delete($this);
	}

	public static function find($id): ?self {
		return DatabaseEntity::find(get_called_class(), $id);
	}

	public static function findBy(array $whereFields): ?self {
		return DatabaseEntity::findBy(get_called_class(), $whereFields);
	}

	public static function all(int $qntPerPage = null, int $page = null, array $orderBy = null): iterable {
		return DatabaseEntity::all(get_called_class(), $qntPerPage, $page, $orderBy);
	}

	public static function search(array $fieldsName = null, array $whereFields = null, int $qntPerPage = null, int $page = null, array $orderBy = null): iterable {
		return DatabaseEntity::search(get_called_class(), $fieldsName, $whereFields, $qntPerPage, $page, $orderBy);
	}

	public static function deleteWithFilter(Array $fieldsFilter): bool {
		return DatabaseEntity::deleteWithFilter(get_called_class(), $fieldsFilter);
	}
}