<?php

/**
 * Class for working with binary tree
 *
 * @author gumione
 */
class BinaryTreeControl extends BinaryTree
{

    public function createCell($parent_id, $position)
    {
        if (!in_array($position, [1, 2])) {
            return false;
        }

        $parent = $this->getCellById($parent_id);

        if (!$parent) {
            return false;
        }

        if ($this->checkCellExists($parent_id, $position)) {
            return false;
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

        $this->saveCell($cell_data, $cell_id);
    }

    public function getCellsByLevel($level)
    {
        $statement = $this->db->prepare("SELECT * FROM tree WHERE level=:level");
        $statement->bindParam(":level", $level);
        $statement->execute();
        return $statement->fetchAll();
    }

    public function fillTree($level = 5)
    {
        $current_level = 1;
        $possible_cells = 2;

        while ($current_level < $level) {
            $position = 1;

            $current_level_cells = $this->getCellsByLevel($current_level);

            foreach ($current_level_cells as $c) {
                $position = ($position > 2) ? 1 : $position;
                for ($i = 1; $i <= 2; $i++) {
                    $this->createCell($c['id'], $position);
                    $position++;
                }
            }

            $current_level++;
            $possible_cells *= 2;
        }

        return $result = ['status' => 'success', 'message' => 'Дерево успешно заполнено'];
    }

    public function getNodes($id = 1, $direction = 'down')
    {
        if ($direction == 'down') {
            $statement = $this->db->prepare('SELECT * FROM tree WHERE path LIKE :q AND id != :id ORDER BY path  ASC');
            $statement->bindValue(":q", '%.' . $id . '.%');
            $statement->bindValue(":id", $id);
        } else if ($direction == 'up') {
            $start_node = $this->getCellById($id);
            $path = explode('.', $start_node['path']);
            unset($path[count($path) - 1]);

            foreach ($path as $k => $p) {
                if ($p != '0') {
                    $in_str[] = implode('.', $path);
                    unset($path[count($path) - 1]);
                }
            }
            
            $statement = $this->db->prepare('SELECT * FROM tree WHERE path IN("' . implode('", "', $in_str) . '") ORDER BY FIELD(path,"' . implode('", "', array_reverse($in_str)) . '")');
        }

        $statement->execute();

        $nodes = $statement->fetchAll();

        return $nodes;
    }

    public function moveNode($id, $target_id, $position)
    {
        if ($id == 1) {
            return ['status' => 'danger', 'message' => 'Невозможно переместить корневую ячейку'];
        }

        if (!in_array($position, [1, 2])) {
            return ['status' => 'danger', 'message' => 'Можно добавлять ячейки только слева или справа'];
        }

        $parent = $this->getCellById($target_id);

        if (!$parent) {
            return ['status' => 'danger', 'message' => 'Родительской ячейки с таким ID не существует'];
        }

        if ($this->checkCellExists($target_id, $position)) {
            return ['status' => 'danger', 'message' => 'Такая ячейка уже существует'];
        }

        $source = $this->getCellById($id);
        $child_nodes = $this->getNodes($id, 'down');

        foreach ($child_nodes as $cn) {
            $cn_ids[] = $cn['id'];
        }
		
        if (!empty($cn_ids)) {
            if (in_array($parent['id'], $cn_ids)) {
                return ['status' => 'danger', 'message' => 'Невозможно переместить родительскую ячейку в дочернюю'];
            }
        }

        $target_path = $parent['path'];
        $source_path = explode('.', $source['path']);

        unset($source_path[count($source_path) - 1]);
        $source_path = implode('.', $source_path);
        $new_source_path = str_replace($source_path, $target_path, $source_path) . '.' . $id;
        $new_level = $parent['level'] + 1;

        try {

            $this->db->beginTransaction();

            foreach ($child_nodes as $cn) {
                $new_child_level = $new_level + ($cn['level'] - $source['level']);
                $child_path = str_replace($source['path'], $target_path . '.' . $id, $cn['path']);
                $this->db->exec("UPDATE tree SET path = '{$child_path}', level = {$new_child_level} WHERE id={$cn['id']}");
            }

            $this->db->exec("UPDATE tree SET path = '{$new_source_path}', parent_id = {$target_id}, position = {$position}, level = {$new_level} WHERE id={$id}");
            $this->db->commit();
        } catch (Exception $e) {
			print_r($e);
            $this->db->rollBack();
            return ['status' => 'danger', 'message' => 'Перемещение не удалось'];
        }

        return ['status' => 'success', 'message' => 'Ячейки успешно перемещены'];
    }
}
