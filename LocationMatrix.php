<?php

class LocationMatrix
{
    const A = 6378137;//地球长半径
    const B = 6356752.3142;//短半径
    const F = 1 / 298.257223563;//扁率
    private $radius = 500; //半径
    const NORTH_ANGLE = 0; //北方
    const EAST_ANGLE = 90; //东方

    private $matrix = [];
    private $bottomLatLimit; //下边界
    private $topLatLimit;//上边界
    private $leftLngLimit;//左边界
    private $rightLngLimit;//右边界

    public function handle()
    {
        [$leftTop['lng'], $leftTop['lat']] = explode(",","116.210722,39.946979"); // 左上角顶点
        [$rightTop['lng'], $rightTop['lat']] = explode(",","116.30472,39.950851");// 右上角顶点
        [$leftBottom['lng'], $leftBottom['lat']] = explode(",","116.225526,39.897289");// 左下角顶点
        [$rightBottom['lng'], $rightBottom['lat']] = explode(",","116.295522,39.901938");// 右下角顶点
        $this->radius = 500; // 半径

        $this->initSlidePoint($leftTop, $rightTop, $leftBottom, $rightBottom);

        $matrixLeftBottom = $this->getLocationByAngleAndDistance($this->leftLngLimit, $this->bottomLatLimit, self::EAST_ANGLE / 2, $this->radius / sin(self::EAST_ANGLE / 2)); // 矩阵中左上角的点
        $this->getMatrix($matrixLeftBottom);
        var_export($this->matrix);
        echo count($this->matrix);
    }

    /**
     * 初始化矩阵的边界值
     *
     * @param $leftTop
     * @param $rightTop
     * @param $leftBottom
     * @param $rightBottom
     */
    private function initSlidePoint($leftTop, $rightTop, $leftBottom, $rightBottom)
    {
        $this->bottomLatLimit = max($rightBottom['lat'], $leftBottom['lat']);
        $this->topLatLimit = min($leftTop['lat'], $rightTop['lat']);
        $this->leftLngLimit = max($leftTop['lng'], $leftBottom['lng']);
        $this->rightLngLimit = min($rightTop['lng'], $rightBottom['lng']);
    }

    /**
     * @param $lng 坐标经度
     * @param $lat 坐标纬度
     * @param $angle 偏移的角度（正北方0度，正右方90度，正下方180度...）
     * @param $distance 物理距离（m）
     * @return array 计算之后的坐标
     */
    private function getLocationByAngleAndDistance($lng, $lat, $angle, $distance)
    {
        $alpha1 = deg2rad($angle);
        $sinAlpha1 = sin($alpha1);
        $cosAlpha1 = cos($alpha1);

        $tanU1 = (1 - self::F) * tan(deg2rad($lat));
        $cosU1 = 1 / sqrt((1 + $tanU1 * $tanU1));
        $sinU1 = $tanU1 * $cosU1;
        $sigma1 = atan2($tanU1, $cosAlpha1);
        $sinAlpha = $cosU1 * $sinAlpha1;
        $cosSqAlpha = 1 - $sinAlpha * $sinAlpha;
        $uSq = $cosSqAlpha * (self::A * self::A - self::B * self::B) / (self::B * self::B);
        $A = 1 + $uSq / 16384 * (4096 + $uSq * (-768 + $uSq * (320 - 175 * $uSq)));
        $B = $uSq / 1024 * (256 + $uSq * (-128 + $uSq * (74 - 47 * $uSq)));

        $sigma = $distance / (self::B * $A);
        $sigmaP = 2 * pi();
        while (abs($sigma - $sigmaP) > 1e-12) {
            $cos2SigmaM = cos(2 * $sigma1 + $sigma);
            $sinSigma = sin($sigma);
            $cosSigma = cos($sigma);
            $deltaSigma = $B * $sinSigma * ($cos2SigmaM + $B / 4 * ($cosSigma * (-1 + 2 * $cos2SigmaM * $cos2SigmaM) -
                        $B / 6 * $cos2SigmaM * (-3 + 4 * $sinSigma * $sinSigma) * (-3 + 4 * $cos2SigmaM * $cos2SigmaM)));
            $sigmaP = $sigma;
            $sigma = $distance / (self::B * $A) + $deltaSigma;
        }

        $tmp = $sinU1 * $sinSigma - $cosU1 * $cosSigma * $cosAlpha1;
        $lat2 = atan2($sinU1 * $cosSigma + $cosU1 * $sinSigma * $cosAlpha1,
            (1 - self::F) * sqrt($sinAlpha * $sinAlpha + $tmp * $tmp));
        $lambda = atan2($sinSigma * $sinAlpha1, $cosU1 * $cosSigma - $sinU1 * $sinSigma * $cosAlpha1);
        $C = self::F / 16 * $cosSqAlpha * (4 + self::F * (4 - 3 * $cosSqAlpha));
        $L = $lambda - (1 - $C) * self::F * $sinAlpha *
            ($sigma + $C * $sinSigma * ($cos2SigmaM + $C * $cosSigma * (-1 + 2 * $cos2SigmaM * $cos2SigmaM)));

        $lngLatObj = [
            'lng' => $lng + rad2deg($L),
            'lat' => rad2deg($lat2)
        ];
        return $lngLatObj;
    }

    /**
     * @param $startLocation 起始位置
     */
    public function getMatrix($startLocation)
    {
        $matrixLocation = $startLocation; // 从左下角的点开始进行移动
        $this->matrix[] = $matrixLocation;
        while ($matrixLocation['lng'] <= $this->rightLngLimit) {// 在地图上向右移动，不能超过右边界
            while ($matrixLocation['lat'] <= $this->topLatLimit) { // 从地图上向上移动，不能超过上边界
                $this->matrix[] = $matrixLocation;
                $matrixLocation = $this->getLocationByAngleAndDistance($matrixLocation['lng'], $matrixLocation['lat'], self::NORTH_ANGLE, 2 * $this->radius);
            }
            $startLocation = $this->getLocationByAngleAndDistance($startLocation['lng'], $startLocation['lat'], self::EAST_ANGLE, 2 * $this->radius);
            $matrixLocation = $startLocation;
        }
        $this->genJsCode();
    }

    public function genJsCode()
    {
        $firstLocation = $this->matrix[0];
        $jsCode = "var map = new BMap.Map(\"allmap\");\n
        var point = new BMap.Point(" . implode(",", array_values($firstLocation)) . ");\n
        map.centerAndZoom(point, 15);\n
        map.enableScrollWheelZoom();\n
        // 编写自定义函数,创建标注\n
        function addMarker(point){\n
          var marker = new BMap.Marker(point);\n
          map.addOverlay(marker);\n
        }\n";
        foreach ($this->matrix as $location) {
            $jsCode .= "addMarker(new BMap.Point(" . implode(",", array_values($location)) . "));\n";
        }
        echo $jsCode;
    }
}

(new LocationMatrix())->handle();
