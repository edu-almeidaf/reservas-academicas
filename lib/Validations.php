<?php

namespace Lib;

use Core\Database\ActiveRecord\Model;
use Core\Database\Database;
use DateTime;

class Validations
{
    public static function notEmpty($attribute, $obj)
    {
        if ($obj->$attribute === null || $obj->$attribute === '') {
            $obj->addError($attribute, 'não pode ser vazio!');
            return false;
        }

        return true;
    }

    public static function passwordConfirmation($obj)
    {
        if ($obj->password !== $obj->password_confirmation) {
            $obj->addError('password', 'as senhas devem ser idênticas!');
            return false;
        }

        return true;
    }

    public static function email(string $attribute, Model $obj): bool
    {
        $value = $obj->$attribute;

        if ($value === null || $value === '') {
            return true;
        }

        if (filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            $obj->addError($attribute, 'não é um e-mail válido!');
            return false;
        }

        return true;
    }

    /**
     * @param array<int, mixed> $values
     */
    public static function inclusion(string $attribute, array $values, Model $obj): bool
    {
        if (!in_array($obj->$attribute, $values, true)) {
            $obj->addError($attribute, 'não é um valor válido!');
            return false;
        }

        return true;
    }

    /**
     * @param class-string<Model> $modelClass
     */
    public static function existsIn(string $attribute, string $modelClass, Model $obj): bool
    {
        $value = $obj->$attribute;

        if ($value === null || $value === '') {
            return true;
        }

        if (!is_int($value) || $modelClass::findById($value) === null) {
            $obj->addError($attribute, 'não é um valor válido!');
            return false;
        }

        return true;
    }

    public static function date(string $attribute, Model $obj): bool
    {
        return self::dateTimeFormat($attribute, 'Y-m-d', 'não é uma data válida!', $obj);
    }

    public static function time(string $attribute, Model $obj): bool
    {
        return self::dateTimeFormat($attribute, 'H:i', 'não é um horário válido!', $obj);
    }

    public static function uniqueness($fields, $object)
    {
        $dbFieldsValues = [];
        $objFieldValues = [];

        if (!is_array($fields)) {
            $fields = [$fields];
        }

        if (!$object->newRecord()) {
            $dbObject = $object::findById($object->id);

            foreach ($fields as $field) {
                $dbFieldsValues[] = $dbObject->$field;
                $objFieldValues[] = $object->$field;
            }

            if (
                !empty($dbFieldsValues) &&
                !empty($objFieldValues) &&
                $dbFieldsValues === $objFieldValues
            ) {
                return true;
            }
        }

        $table = $object::table();
        $conditions = implode(' AND ', array_map(fn($field) => "{$field} = :{$field}", $fields));

        $sql = <<<SQL
            SELECT id FROM {$table} WHERE {$conditions};
        SQL;

        $pdo = Database::getDatabaseConn();
        $stmt = $pdo->prepare($sql);

        foreach ($fields as $field) {
            $stmt->bindValue($field, $object->$field);
        }

        $stmt->execute();

        if ($stmt->rowCount() !== 0) {
            foreach ($fields as $field) {
                $object->addError($field, 'já existe um registro com esse dado');
            }
            return false;
        }

        return true;
    }

    private static function dateTimeFormat(string $attribute, string $format, string $message, Model $obj): bool
    {
        $value = $obj->$attribute;

        if ($value === null || $value === '') {
            return true;
        }

        $parsed = is_string($value) ? DateTime::createFromFormat('!' . $format, $value) : false;

        if ($parsed === false || $parsed->format($format) !== $value) {
            $obj->addError($attribute, $message);
            return false;
        }

        return true;
    }
}
