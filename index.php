<?php
require_once 'BinaryTree.php';
require_once 'BinaryTreeControl.php';

$dsn = "mysql:host=localhost;port=3306;dbname=ukrtech;charset=utf8";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
];

$pdo = new PDO($dsn, 'root', '', $options);

$tree = new BinaryTree($pdo);

?>


<html>
    <head>
        <link rel="stylesheet" href="/assets/css/bootstrap.min.css">
        <link rel="stylesheet" href="/assets/css/treant.css">
    </head>
    <body>

        <div class="container-fluid">
            <h2 class="text-center">Тестовое задание</h2>
            <div class="row">
                <div class="col-lg-3">
                    <h3>Создание ячейки</h3>
                    <hr/>
                    <?php
                    if (!empty($_POST) AND filter_input(INPUT_POST, 'action') == 'create') {
                        $result = $tree->createCell(filter_input(INPUT_POST, 'parent_id'), filter_input(INPUT_POST, 'position'));
                    }

                    ?>
                    <?php if ($result): ?>
                        <div class="alert alert-<?= $result['status'] ?>" role="alert">
                            <?= $result['message'] ?>
                        </div>
                    <?php endif; ?>

                    <form method="post">
                        <input type="hidden" name="action" value="create"/>
                        <div class="form-group">
                            <label for="parent_id">Parent ID</label>
                            <input type="text" class="form-control" id="parent_id" name="parent_id" value="<?= htmlspecialchars(filter_input(INPUT_POST, 'parent_id')) ?>">
                        </div>
                        <div class="form-group">
                            <label for="position">Position</label>
                            <input type="text" class="form-control" id="position" name="position" value="<?= htmlspecialchars(filter_input(INPUT_POST, 'position')) ?>">
                        </div>
                        <button type="submit" class="btn btn-primary">Создать</button>
                    </form>
                </div>
                <div class="col-lg-3">
                    <h3>Заполнение дерева</h3>
                    <hr/>
                    <?php
                    if (!empty($_POST) AND filter_input(INPUT_POST, 'action') == 'fill') {
                        $tree_control = new BinaryTreeControl($pdo);
                        $result = $tree_control->fillTree(filter_input(INPUT_POST, 'level'));
                    }

                    ?>
                    <?php if ($result): ?>
                        <div class="alert alert-<?= $result['status'] ?>" role="alert">
                            <?= $result['message'] ?>
                        </div>
                    <?php endif; ?>
                    <form method="post">
                        <input type="hidden" name="action" value="fill"/>
                        <div class="form-group">
                            <label for="level">До уровня</label>
                            <input type="text" class="form-control" id="level" name="level" value="<?= (filter_input(INPUT_POST, 'level')) ? htmlspecialchars(filter_input(INPUT_POST, 'level')) : '5' ?>">
                        </div>
                        <button type="submit" class="btn btn-primary">Заполнить</button>
                    </form>
                </div>
                <div class="col-lg-3">
                    <h3>Получить ячейки</h3>
                    <hr/>
                    <?php
                    if (!empty($_POST) AND filter_input(INPUT_POST, 'action') == 'get') {
                        $tree_control = new BinaryTreeControl($pdo);
                        $nodes = $tree_control->getNodes(filter_input(INPUT_POST, 'id'), filter_input(INPUT_POST, 'direction'));
                    }

                    ?>
                    <form method="post">
                        <input type="hidden" name="action" value="get"/>
                        <div class="form-group">
                            <label for="id">ID</label>
                            <input type="text" class="form-control" id="id" name="id" value="<?= (filter_input(INPUT_POST, 'id')) ? htmlspecialchars(filter_input(INPUT_POST, 'id')) : '1' ?>">
                        </div>
                        <div class="form-group">
                            <label for="direction">Направление</label>
                            <select name="direction" id="direction" class="form-control">
                                <option value="down"<?php if (filter_input(INPUT_POST, 'direction') == 'down') echo ' selected="selected"' ?>>Вниз</option>
                                <option value="up"<?php if (filter_input(INPUT_POST, 'direction') == 'up') echo ' selected="selected"' ?>>Вверх</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Показать</button>
                    </form>
                    <?php if ($nodes): ?>
                        <hr/>
                        <ul>
                            <?php foreach ($nodes as $n): ?>
                                <li><?= $n['id'] ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
            <hr/>
            <h2>Структура дерева</h2>
            <div class="row justify-content-center">
                <div class="col-lg-12 col-md-12">
                    <div id="tree"></div>
                </div>
            </div>

            <?php $cells = $tree->getTreeStructure(); ?>

        </div>
    </body>

    <script src="/assets/js/treant/vendor/jquery.min.js"></script>
    <script src="/assets/js/treant/vendor/raphael.js"></script>
    <script src="/assets/js/treant/Treant.js"></script>

    <script type="text/javascript">
        config = {
        container: "#tree",
                rootOrientation: "NORTH",
                connectors: {
                type: "step",
                        style: {
                        "stroke-width": 2,
                                "stroke": "#ccc"
                        }
                }
        };
        chart_config = [
                config
        ]

<?php foreach ($cells as $k => $c): ?>
    <?= $k ?> = {
    <?php if (!$c['parent']): ?>
                text: {name: '<?= $c['name'] ?>'}
    <?php else: ?>
                parent: <?= $c['parent'] ?>,
                        text: {name: '<?= $c['name'] ?>'}
    <?php endif; ?>
            }

            chart_config.push(<?= $k ?>);
<?php endforeach; ?>
        var chart = new Treant(chart_config);
    </script>
</html>
