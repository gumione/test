<?php

/**
 * Class for working with binary tree
 *
 * @author gumione
 */
class BinaryTree
{

    protected $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getCellById($id)
    {
        $statement = $this->db->prepare("SELECT * FROM tree WHERE id=:id");
        $statement->bindParam(":id", $id);
        $statement->execute();
        return $statement->fetch();
    }

    public function checkCellExists($parent_id, $position)
    {
        $statement = $this->db->prepare("SELECT * FROM tree WHERE parent_id=:parent_id AND position=:position");
        $statement->bindParam(":parent_id", $parent_id);
        $statement->bindParam(":position", $position);
        $statement->execute();
        return $statement->fetch();
    }   

    public function getTreeStructure()
    {
        $statement = $this->db->prepare("SELECT * FROM tree ORDER BY parent_id ASC, position ASC");
        $statement->execute();
        $cells = $statement->fetchAll();
        $tree = [];
        
        foreach($cells as $c) {
            if($c['parent_id'] != 0) {
                $tree['object_' . $c['id']] = ['parent' => 'object_' . $c['parent_id'], 'name' => $c['id']];
            } else {
                $tree['object_' . $c['id']] = ['name' => $c['id']];
            }
        }
        
        return $tree;
    }

    public function createCell($parent_id, $position)
    {
        $parent = $this->getCellById($parent_id);

        if (!$parent) {
            return ['status' => 'danger', 'message' => 'Родительской ячейки с таким ID не существует'];
        }

        if ($this->checkCellExists($parent_id, $position)) {
            return ['status' => 'danger', 'message' => 'Такая ячейка уже существует'];
        }
        $cell_data = [
            'parent_id' => $parent_id,
            'position' => $position,
            'path' => strval($parent['path'] . '.'),
            'level' => $parent['level'] + 1
        ];
        $cell_id = $this->saveCell($cell_data);

        $cell_data = [
            'path' => $cell_data['path'] . $cell_id
        ];

        if ($this->saveCell($cell_data, $cell_id) === false) {
            $result = ['status' => 'danger', 'message' => 'Что-то пошло не так'];
        } else {
            $result = ['status' => 'success', 'message' => 'Ячейка #' . $cell_id . ' успешно добавлена'];
        }

        return $result;
    }

    public function saveCell($data, $id = null)
    {
        if ($id === null) {
            $statement = $this->db->prepare('INSERT INTO tree(' . implode(',', array_keys($data)) . ') ' . 'values (:' . implode(',:', array_keys($data)) . ')');
            foreach ($data as $k => $v) {
                $statement->bindValue(':' . $k, $v);
            }

            $statement->execute();

            return $this->db->lastInsertId();
        } else {
            foreach ($data as $k => $v) {
                $fields[] = $k . ' = :' . $k;
            }

            $statement = $this->db->prepare('update tree SET ' . implode(',', $fields) . ' WHERE id=:id');
            $statement->bindValue(':id', $id);

            foreach ($data as $k => $v) {
                $statement->bindValue(':' . $k, $v);
            }

            return $statement->execute();
        }
    }
    
    private function _buildTree(&$cells, $parent_id = 0)
    {
        $tree = [];

        foreach ($cells as $c) {
            if ($c['parent_id'] == $parent_id) {
                $sub = $this->_buildTree($cells, $c['id']);
                if ($sub) {
                    $c['sub'] = $sub;
                }
                $tree[$c['id']] = $c;
                unset($cells[$c['id']]);
            }
        }
        return $tree;
    }
}
