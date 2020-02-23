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
    
    public function getNodes($id = 1, $direction = 'down') {        
        if($direction == 'down') {
            $statement = $this->db->prepare('SELECT * FROM tree WHERE path LIKE :q AND id != :id');
            $statement->bindValue(":q", '%.' . $id . '%');
            $statement->bindValue(":id", $id);
        } else if ($direction == 'up') {
            $start_node = $this->getCellById($id);
            $path = explode('.', $start_node['path']);
            unset($path[count($path) - 1]);
            
            foreach ($path as $k => $p) {
                $in[] = ':p'.$k;
            }
            
            $in_str = implode(',', $in);
            
            $statement = $this->db->prepare("SELECT * FROM tree WHERE path IN({$in_str})");
            
            foreach ($path as $k => $p) {
                $statement->bindValue(':p'.$k, implode('.', $path));
                unset($path[count($path) - 1]);
            }
        }
        
        $statement->execute();
        
        $nodes = $statement->fetchAll();
        
        return $nodes;
    }
}
