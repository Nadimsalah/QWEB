<?php $res = array("TypeSlider"=>"Slider"); $result2 = array(array("FoodID"=>"123")); array_splice($res, 101, 102, array($result2[0])); echo json_encode($res); ?>
