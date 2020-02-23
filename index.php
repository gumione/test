<?php
require_once 'BinaryTree.php';

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

        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-4">

                    <?php
                    if (!empty($_POST)) {
                        $result = $tree->createCell($_POST['parent_id'], $_POST['position']);
                    }

                    ?>
                    <?php if ($result): ?>
                        <div class="alert alert-<?= $result['status'] ?>" role="alert">
                            <?= $result['message'] ?>
                        </div>
                    <?php endif; ?>

                    <form method="post">
                        <div class="form-group">
                            <label for="parent_id">Parent ID</label>
                            <input type="text" class="form-control" id="parent_id" name="parent_id" value="<?= htmlspecialchars($_POST['parent_id']) ?>">
                        </div>
                        <div class="form-group">
                            <label for="position">Position</label>
                            <input type="text" class="form-control" id="position" name="position" value="<?= htmlspecialchars($_POST['position']) ?>">
                        </div>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </form>
                </div>
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-12 col-md-12">
                    <div id="tree"> </div>
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
