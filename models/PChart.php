<?php 

 namespace app\models;

 require_once("pchart/class/pData.class.php");
 require_once("pchart/class/pDraw.class.php");
 require_once("pchart/class/pImage.class.php"); 

 class PChart {

 	public static function drawAllStat($valArr, $days)
 	{
 		$width = 200 + 100*$days;
 		$height = 600;

 		$MyData = new \pData();  
 		foreach ($valArr as $name => $value) {
 			$MyData->addPoints($value, $name);
 		}
 		$dates = [];
 		$time = time();
 		for ($i=$days-1; $i >= 0; $i--) { 
 			$dates[] = date("d.m.y", time() - ($i * 60 * 60 * 24));	
 		}

 		$MyData->setAxisName(0,"Количество символов");
 		$MyData->setAxisUnit(0,"");
 		$MyData->addPoints($dates,"Labels");
 		$MyData->setSerieDescription("Labels","Дни");
 		$MyData->setAbscissa("Labels"); 


 		/* Create the pChart object */
 		$myPicture = new \pImage($width, $height, $MyData);

 		/* Draw the background */
 		$Settings = array("R"=>180, "G"=>180, "B"=>180, "Dash"=>1, "DashR"=>130, "DashG"=>130, "DashB"=>130);
 		$myPicture->drawFilledRectangle(0,0,$width, $height, $Settings); 

 		/* Overlay with a gradient */
 		$Settings = array("StartR"=>219, "StartG"=>231, "StartB"=>139, "EndR"=>1, "EndG"=>138, "EndB"=>68, "Alpha"=>30);
 		$myPicture->drawGradientArea(0,0,$width, $height, DIRECTION_VERTICAL,$Settings);

 		/* Add a border to the picture */
 		$myPicture->drawRectangle(0,0,$width-1,$height-1,array("R"=>0,"G"=>0,"B"=>0)); 

 		/* Write the chart title */ 
 		$myPicture->setFontProperties(array("FontName"=>__DIR__."/pchart/fonts/calibri.ttf","FontSize"=>11));
 		$myPicture->drawText(155,40,"Общая статистика за $days дн.",array("FontSize"=>20,"Align"=>TEXT_ALIGN_BOTTOMMIDDLE));

 		/* Draw the scale and the 1st chart */
 		$myPicture->setGraphArea(60,60,$width-130,$height-40);
 		$myPicture->drawFilledRectangle(60,60,$width-130,$height-40,array("R"=>255,"G"=>255,"B"=>255,"Surrounding"=>-200,"Alpha"=>10));
 		$myPicture->setFontProperties(array("FontName"=>__DIR__."/pchart/fonts/verdana.ttf","FontSize"=>7));
 		$myPicture->drawScale(array("DrawSubTicks"=>TRUE));
 		$myPicture->setShadow(TRUE,array("X"=>1,"Y"=>1,"R"=>0,"G"=>0,"B"=>0,"Alpha"=>10));
 		$myPicture->drawSplineChart();
 		$myPicture->setShadow(FALSE);

 		/* Write the chart legend */
 		$myPicture->drawLegend($width - 120, 60,array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_VERTICAL ));

 		$myPicture->setShadow(TRUE,array("X"=>1,"Y"=>1,"R"=>0,"G"=>0,"B"=>0,"Alpha"=>10));
 		$myPicture->setFontProperties(array("FontName"=>__DIR__."/pchart/fonts/calibri.ttf","FontSize"=>11));

 		/* Render the picture (choose the best way) */

 		$dir = __DIR__."/../web/images/pic.png";
 		$myPicture->autoOutput($dir);
 		return $dir;
 	}

 }