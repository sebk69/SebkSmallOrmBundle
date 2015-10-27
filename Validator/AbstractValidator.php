<?php

namespace Sebk\SmallOrmBundle\Validator;

use Sebk\SmallOrmBundle\Factory\Dao;
use Sebk\SmallOrmBundle\Dao\Model;

abstract class AbstractValidator {

    protected $daoFactory;
    protected $model;
    protected $message;

    public function __construct(Dao $daoFactory, Model $model) {
        $this->model = $model;
        $this->daoFactory = $daoFactory;
    }

    /* public function testRelation($property, $table, $idTable) {
      $daoCible = $this->factory->getDao($table);
      $whereArray = array(
      array(
      "modelFieldName" => $idTable,
      "operator" => "=",
      "valeur" => $this->model->$property,
      ),
      );
      $result = $daoCible->select($whereArray);

      return count($result) == 1;
      } */

    abstract public function validate();

    public function testNonEmpty($field) {
        $method = "get" . $field;
        if ($this->model->$method !== null && $this->model->$method != "") {
            return true;
        }

        return false;
    }

    public function getMessage() {
        return $this->message;
    }

    public function testUnique($field) {
        $dao = $this->dao->get($this->model->getBundle(), $this->model->getModelName());
        $creation = !$this->model->fromDb;

        $query = $dao->createQueryBuilder("unique");
        $where = $query->where();
        $method = "get" . $field;

        if ($creation) {
            $result = $dao->findBy(array($field => $this->model->$method()));
        } else {
            $first = true;
            foreach ($this->model->getPrimaryKeys() as $key => $value) {
                if ($first) {
                    $where->firstCondition($query->getFieldForCondition($key), "=", ":" . $key . "Primary");
                    $query->setParameter($key . "Primary", $value);
                } else {
                    $where->andCondition($query->getFieldForCondition($key), "=", ":" . $key . "Primary");
                    $query->setParameter($key . "Primary", $value);
                }
            }

            $where->andCondition($query->getFieldForCondition($field), "=", ":" . $field);
            $query->setParameter($field, $this->model->$method());

            $result = $dao->getResult($query);
        }

        return count($result) == 0;
    }

}
