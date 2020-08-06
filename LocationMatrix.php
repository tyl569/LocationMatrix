<?php

require_once "./Location.php";

class LocationMatrix
{
    const NORTH_ANGLE = 0; //半径
    const EAST_ANGLE = 90; //北方
    private $radius = 500; //东方
    private $matrix;
    private $bottomLatLimit; //下边界
    private $topLatLimit;//上边界
    private $leftLngLimit;//左边界
    private $rightLngLimit;//右边界

    public function __construct($leftTop, $rightTop, $leftBottom, $rightBottom)
    {
        $this->initSlidePoint($leftTop, $rightTop, $leftBottom, $rightBottom);
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
        $rightBottom = explode(',', $rightBottom);
        $leftTop = explode(',', $leftTop);
        $leftBottom = explode(',', $leftBottom);
        $rightTop = explode(',', $rightTop);

        $this->bottomLatLimit = max($rightBottom[0], $leftBottom[0]);
        $this->topLatLimit = min($leftTop[0], $rightTop[0]);
        $this->leftLngLimit = max($leftTop[1], $leftBottom[1]);
        $this->rightLngLimit = min($rightTop[1], $rightBottom[1]);
    }

    public function handle()
    {
        $matrixLeftBottom = Location::calLocation($this->leftLngLimit, $this->bottomLatLimit, self::EAST_ANGLE / 2, $this->radius / sin(self::EAST_ANGLE / 2));
        $this->calLocation($matrixLeftBottom);
    }

    public function getMatrix()
    {
        return $this->matrix;
    }

    /**
     * 计算位置信息
     *
     * @param $startLocation
     */
    public function calLocation($startLocation)
    {
        $matrixLocation = $startLocation;
        $this->matrix[] = $matrixLocation;
        while ($matrixLocation['lng'] <= $this->rightLngLimit) {
            while ($matrixLocation['lat'] <= $this->topLatLimit) {
                $this->matrix[] = $matrixLocation;
                $matrixLocation = Location::calLocation($matrixLocation['lng'], $matrixLocation['lat'], self::NORTH_ANGLE, 2 * $this->radius);
            }
            $startLocation = Location::calLocation($startLocation['lng'], $startLocation['lat'], self::EAST_ANGLE, 2 * $this->radius);
            $matrixLocation = $startLocation;
        }
    }

    /**
     * @param int $radius
     */
    public function setRadius(int $radius)
    {
        $this->radius = $radius;
    }
}



