<?php
namespace fw\database;

abstract class Entity {

	public function load(): bool {
		return DatabaseEntity::load($this);
	}

	public function insert(): bool {
		return DatabaseEntity::insert($this);
	}

	public function update(): bool {
		return DatabaseEntity::update($this);
	}

	public function delete(): bool {
		return DatabaseEntity::delete($this);
	}

	public static function find($id): Entity {
		return DatabaseEntity::find(get_called_class(), $id);
	}

	public static function all(): iterable {
		return DatabaseEntity::all(get_called_class());
	}

	public static function deleteWithFilter(Array $fieldsFilter): bool {
		return DatabaseEntity::deleteWithFilter(get_called_class(), $fieldsFilter);
	}
}

