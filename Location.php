<?php


class Location
{
    const A = 6378137;//地球长半径
    const B = 6356752.3142;//短半径
    const F = 1 / 298.257223563;//扁率

    /**
     * 根据经纬度+偏移角度+距离计算坐标
     *
     * @param $lng
     * @param $lat
     * @param $angle
     * @param $distance
     * @return array
     */
    public static function calLocation($lng, $lat, $angle, $distance)
    {
        $alpha1 = deg2rad($angle);
        $sinAlpha1 = sin($alpha1);
        $cosAlpha1 = cos($alpha1);

        $tanU1 = (1 - self::F) * tan(deg2rad($lat));
        $cosU1 = 1 / sqrt((1 + $tanU1 * $tanU1));
        $sinU1 = $tanU1 * $cosU1;
        $sigma1 = atan2($tanU1, $cosAlpha1);
        $sinAlpha = $cosU1 * $sinAlpha1;
        $cosSqAlpha = 1 - pow($sinAlpha, 2);
        $uSq = $cosSqAlpha * (self::A * self::A - self::B * self::B) / (self::B * self::B);
        $A = 1 + $uSq / 16384 * (4096 + $uSq * (-768 + $uSq * (320 - 175 * $uSq)));
        $B = $uSq / 1024 * (256 + $uSq * (-128 + $uSq * (74 - 47 * $uSq)));

        $sigma = $distance / (self::B * $A);
        $sigmaP = 2 * pi();
        while (abs($sigma - $sigmaP) > 1e-12) {
            $cos2SigmaM = cos(2 * $sigma1 + $sigma);
            $sinSigma = sin($sigma);
            $cosSigma = cos($sigma);
            $deltaSigma = $B * $sinSigma * ($cos2SigmaM + $B / 4 * ($cosSigma * (-1 + 2 * pow($cos2SigmaM, 2)) -
                        $B / 6 * $cos2SigmaM * (-3 + 4 * pow($sinSigma, 2)) * (-3 + 4 * pow($cos2SigmaM, 2))));
            $sigmaP = $sigma;
            $sigma = $distance / (self::B * $A) + $deltaSigma;
        }

        $tmp = $sinU1 * $sinSigma - $cosU1 * $cosSigma * $cosAlpha1;
        $lat2 = atan2($sinU1 * $cosSigma + $cosU1 * $sinSigma * $cosAlpha1,
            (1 - self::F) * sqrt(pow($sinAlpha, 2) + pow($tmp, 2)));
        $lambda = atan2($sinSigma * $sinAlpha1, $cosU1 * $cosSigma - $sinU1 * $sinSigma * $cosAlpha1);
        $C = self::F / 16 * $cosSqAlpha * (4 + self::F * (4 - 3 * $cosSqAlpha));
        $L = $lambda - (1 - $C) * self::F * $sinAlpha *
            ($sigma + $C * $sinSigma * ($cos2SigmaM + $C * $cosSigma * (-1 + 2 * pow($cos2SigmaM, 2))));

        return [
            'lng' => $lng + rad2deg($L),
            'lat' => rad2deg($lat2)
        ];
    }
}