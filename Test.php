<?php

require_once "./LocationMatrix.php";

class Test
{
    public function run()
    {
        $leftTop = "39.946979,116.210722";
        $rightTop = "39.950851,116.30472";
        $leftBottom = "39.897289,116.225526";
        $rightBottom = "39.901938,116.295522";

        $locationMatrix = new LocationMatrix($leftTop, $rightTop, $leftBottom, $rightBottom);
        $locationMatrix->setRadius(500);
        $locationMatrix->handle();
        $firstLocation = $locationMatrix->getMatrix()[0];
        $jsCode = "var map = new BMap.Map(\"allmap\");\n
        var point = new BMap.Point(" . implode(",", array_values($firstLocation)) . ");\n
        map.centerAndZoom(point, 15);\n
        map.enableScrollWheelZoom();\n
        // 编写自定义函数,创建标注\n
        function addMarker(point){\n
          var marker = new BMap.Marker(point);\n
          map.addOverlay(marker);\n
        }\n";
        foreach ($locationMatrix->getMatrix() as $location) {
            $jsCode .= "addMarker(new BMap.Point(" . implode(",", array_values($location)) . "));\n";
        }
        $jsCode.="addMarker(new BMap.Point(116.210722,39.946979));\n";
        $jsCode.="addMarker(new BMap.Point(116.30472,39.950851));\n";
        $jsCode.="addMarker(new BMap.Point(116.225526,39.897289));\n";
        $jsCode.="addMarker(new BMap.Point(116.295522,39.901938));\n";
        echo $jsCode;
    }
}

(new Test())->run();