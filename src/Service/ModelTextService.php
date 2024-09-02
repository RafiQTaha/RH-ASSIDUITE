<?php
namespace App\Service;

use PhpOffice\PhpWord\IOFactory;
use League\HTMLToMarkdown\HtmlConverter;

class ModelTextService
{
    public function getModelContentByRubrique($filename,$rub,$module,$dossier)
    {
        if ($filename == null) {
            return "";
        }
        $titres = $this->getTitres($rub->getId());
        $titre1 = $titres[0];
        $titre2 = $titres[1];
        
        $filename = $dossier->getInscription()->getAnnee()->getFormation()->getEtablissement()->getAbreviation().'/' . $filename;
        $text = $this->getModelText($filename,$titre1,$titre2,$module);
        // $text = $this->convertDocxToHtml($filename,$titre1,$titre2);
        
        return $text;
    }

    
    public function convertDocxToHtml(string $docxFilePath)
    {
        // Load the DOCX file
        // $docxFilePath = __DIR__ . '/models/' . $docxFilePath;
        $docxFilePath = 'models/' . $docxFilePath;
        $phpWord = IOFactory::load($docxFilePath);
        // dd($phpWord);

        // Initialize HTML converter
        $htmlConverter = new HtmlConverter();

        // Iterate through sections and paragraphs
        $html = '';
        $insideDesiredSection = false; // Variable pour suivre si nous sommes à l'intérieur de la section désirée
        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                // Handle paragraphs
                if ($element instanceof \PhpOffice\PhpWord\Element\TextRun) {
                    foreach ($element->getElements() as $text) {
                        $textValue = $text->getText();
                        // dd($textValue);
                        if ($insideDesiredSection) {
                            // Vérifiez si nous avons atteint la fin de la section désirée
                            if (strpos($textValue, '2-') !== false) {
                                $insideDesiredSection = false;
                                break; // Sortir de la boucle dès que nous avons trouvé la fin de la section
                            }
    
                            // Concaténer le texte avec les balises HTML
                            $html .= $htmlConverter->convert($textValue);
                        } else {
                            // Vérifiez si nous avons atteint le début de la section désirée
                            if (strpos($textValue, '1-') !== false) {
                                $insideDesiredSection = true;
                                // Excluez le texte "1-" de la sortie HTML
                                $textValue = substr($textValue, strpos($textValue, '1-') + strlen('1-'));
                            }
                        }

                        // Check if the text value is not null before converting
                        // if ($textValue !== null) {
                        //     $isBold = $text->getFontStyle()->isBold();
                        //     $isUnderline = $text->getFontStyle()->getUnderline();
                        //     if ($isBold) {
                        //         $html .= "<b>". $htmlConverter->convert($textValue)."</b>";
                        //     }elseif ($isUnderline) {
                        //         $html .= "<p style='font-weight:700;'>+ <span>".$htmlConverter->convert($textValue)."</span></p>";
                        //     }else {
                        //         $html .= $htmlConverter->convert($textValue);
                        //     }
                        // }
                    }
                    
                    // $html .= '<br />';
                }
                // Handle tables
                // if ($element instanceof \PhpOffice\PhpWord\Element\Table) {
                //     $html .= '<table style="border-collapse: collapse; border: 1px solid black;">';
                //     foreach ($element->getRows() as $key => $row) {
                //         $html .= '<tr style="height:30px">';
                //         $td = $key == 0 ? 'th' : 'td';
                //         foreach ($row->getCells() as $cell) {
                //             foreach ($cell->getElements() as $cellElement) {
                //                 if ($cellElement instanceof \PhpOffice\PhpWord\Element\TextRun) {
                //                     $html .= '<'.$td.' style="border: 1px solid black;">';
                //                     $html .= $htmlConverter->convert($cellElement->getText());
                //                 }else{
                //                     $html .= '<'.$td.' style="border: 1px solid black;height:30px">';
                //                 }
                //                 $html .= '</'.$td.'>';
                //             }
                //         }
                //         $html .= '</tr>';
                //     }
                //     $html .= '</table>';
                // }
            }
            
            dd($html);
        }

        return $html;
    }

    public function getModelText($filename,$titre1,$titre2,$module)
    {
        $content = '';
        // dd('models/' . $filename);
        $path = 'models/' . $filename;
        // dump($path);
        if (!file_exists($path)) {
            return "";
        }
        $zip = zip_open($path);
        while ($zip_entry = zip_read($zip)) {
            if (zip_entry_open($zip, $zip_entry) == FALSE) continue;
            if (zip_entry_name($zip_entry) != "word/document.xml") continue;
            $content .= zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
            zip_entry_close($zip_entry);
        }
        zip_close($zip);
        $content = str_replace('</w:r></w:p></w:tc><w:tc>', " ", $content);
        $content = str_replace('</w:r></w:p>', "\r\n", $content);
        $text = strip_tags($content);
        
        $text = nl2br($text);
        $from = strpos($text, $titre1);
        $to = strpos($text, $titre2);
        if (empty($titre2)) {
            $to = $from * 2;
        }
        $length = $to - $from;
        $text = substr($text, $from, $length);
        $text = substr($text, strpos($text, "\n") + 1);
        // dd($text);
        $text = explode("<br />\r\n", $text);
        if ($module->getId() == 4) {
            $text = $this->getModelTextDentaire($text);
            // dd($text);
        }elseif ($module->getId() == 5) {
            $text = $this->getModelTextPharmacie($text);
        }else{
            foreach ($text as $nline => $line) {
                if (strpos($line, 'XXX ') !== false) {
                    $line = str_replace("XXX ", "", $line);
                    $line = str_replace($line, "<b>" . $line . "</b>", $line);
                    $text[$nline] = $line;
                } elseif (strpos($line, 'AAA ') !== false) {
                    $line = str_replace("AAA ", "+ ", $line);
                    $line = str_replace($line, "<p style='font-weight:700'>" . $line . "</p>", $line);
                    $text[$nline] = $line;
                }
            }
        }
        
        $text = implode("\r\n", $text);
        // echo nl2br($text);
        // dd(nl2br($text));
        return nl2br($text);
    }

    public function getTitres($rub)
    {
        switch (true) {
            case in_array($rub, [1, 17, 22, 31, 35, 46]):
                $titre1 = "1-";
                $titre2 = "2-";
                break;
        
            case in_array($rub, [3, 18, 23, 32, 36, 47]):
                $titre1 = "2-";
                $titre2 = "3-";
                break;
        
            case in_array($rub, [6, 19, 24, 33, 37, 48]):
                $titre1 = "3-";
                $titre2 = "4-";
                break;
        
            case in_array($rub, [9, 20, 25, 34, 38, 49]):
                $titre1 = "4-";
                $titre2 = "5-";
                break;
        
            case in_array($rub, [7, 10, 21, 26, 39, 50]):
                $titre1 = "5-";
                $titre2 = "the_end";
                break;
        
            case in_array($rub, [27, 40, 51]):
                $titre1 = "6-";
                $titre2 = "7-";
                break;
        
            case in_array($rub, [28, 41, 52]):
                $titre1 = "7-";
                $titre2 = "8-";
                break;
        
            case in_array($rub, [29, 42, 53]):
                $titre1 = "8-";
                $titre2 = "9-";
                break;
        
            case in_array($rub, [30, 43, 54]):
                $titre1 = "9-";
                $titre2 = "the_end";
                break;
        
            case in_array($rub, [44, 55]):
                $titre1 = "10-";
                $titre2 = "11-";
                break;
        
            case $rub == 45:
                $titre1 = "11-";
                $titre2 = "the_end";
                break;
        
            default:
                $titre1 = "";
                $titre2 = "";
                break;
        }
        
        return [$titre1,$titre2];
    }
    
    public function getModelTextPharmacie($text)
    {
        foreach ($text as $nline => $line) {
            if (strpos($line, 'YYY ') !== false) {
                $line = str_replace("YYY ", "", $line);
                $line = str_replace($line, "<h3>" . $line . "</h3>", $line);
                $text[$nline] = $line;
            } elseif (strpos($line, '---- ') !== false) {
                $line = str_replace("---- ", "", $line);
                $text[$nline] = $line;
            } elseif (strpos($line, 'AAA ') !== false) {
                $line = str_replace("AAA ", "", $line);
                $line = str_replace($line, "<h4>" . $line . "</h4>", $line);
                $text[$nline] = $line;
            } elseif (strpos($line, 'SSS ') !== false) {
                $line = str_replace("SSS ", "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", $line);
                $line = str_replace($line, "<b>" . $line . "</b>", $line);
                $text[$nline] = $line;
            } elseif (strpos($line, 'PPP ') !== false) {
                $line = str_replace("PPP ", "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", $line);
                $line = str_replace($line, "</b>" . $line, $line);
                $text[$nline] = $line;
            } elseif (strpos($line, 'BBB ') !== false) {
                $line = str_replace("BBB ", "", $line);
                $line = str_replace($line, "<li>" . $line . "</li>", $line);
                $text[$nline] = $line;
            } elseif (strpos($line, 'CCC') !== false) {
                $line = str_replace("CCC", "Oui <input type='checkbox'/> Non <input type='checkbox'/>", $line);
                $text[$nline] = $line;
            } elseif (strpos($line, 'GGG') !== false) {
                $line = str_replace("GGG", "", $line);
                $line = str_replace($line, "<i>" . $line . "</i>", $line);
                $text[$nline] = $line;
            } elseif (strpos($line, 'FFF') !== false) {
                $line = str_replace("FFF", "<input type='checkbox'/>", $line);
                $text[$nline] = $line;
            } elseif (strpos($line, 'TTT1 ') !== false) {
                $line = str_replace("TTT1 ", "", $line);
                $line = str_replace($line, '<table class="table table-bordered"><thead><tr><td>Spécialité</td><td>DCI</td><td>Posologie</td><td>Mode d’administration</td><td>Indication</td><td>Durée de l’utilisation</td><td>Moment de la prise</td></tr></thead><tbody><tr><td><br></td><td><br></td><td><br></td><td><br></td><td><br></td><td><br></td><td><br></td></tr><tr><td><br></td><td><br></td><td><br></td><td><br></td><td><br></td><td><br></td><td><br></td></tr><tr><td><br></td><td><br></td><td><br></td><td><br></td><td><br></td><td><br></td><td><br></td></tr><tr><td><br></td><td><br></td><td><br></td><td><br></td><td><br></td><td><br></td><td><br></td></tr><tr><td><br></td><td><br></td><td><br></td><td><br></td><td><br></td><td><br></td><td><br></td></tr></tbody></table>', $line);
                $text[$nline] = $line;
            } elseif (strpos($line, 'TTT2 ') !== false) {
                $line = str_replace("TTT2 ", "", $line);
                $line = str_replace($line, '<table class="table table-bordered"><thead><tr><td>Médicaments</td><td>Effets indésirables</td></tr></thead><tbody><tr><td><br></td><td><br></td></tr><tr><td><br></td><td><br></td></tr><tr><td><br></td><td><br></td></tr><tr><td><br></td><td><br></td></tr><tr><td><br></td><td><br></td></tr><tr><td><br></td><td><br></td></tr></tbody></table>', $line);
                $text[$nline] = $line;
            } elseif (strpos($line, 'TTT3 ') !== false) {
                $line = str_replace("TTT3 ", "", $line);
                $line = str_replace($line, '<table class="table table-bordered"><tbody><tr><td>Hypocalorique</td><td><input type="checkbox"/></td></tr><tr><td>Sans sucre</td><td><input type="checkbox"/></td></tr><tr><td>Sans sel</td><td><input type="checkbox"/></td></tr><tr><td>Sans graisse</td><td><input type="checkbox"/></td></tr><tr><td>Autre</td><td> </td></tr></tbody></table>', $line);
                $text[$nline] = $line;
            } elseif (strpos($line, 'MMM ') !== false) {
                $line = str_replace("MMM ", "", $line);
                $line = str_replace($line, "<h3>" . $line . "</h3>", $line);
                $text[$nline] = $line;
            } elseif (strpos($line, 'TTT4 ') !== false) {
                $line = str_replace("TTT4 ", "", $line);
                $line = str_replace($line, "<table class='table table-bordered'><thead><tr><td>DCI</td><td>Forme galénique</td><td>Dosage</td><td>Voie d'administration</td><td>Indications thérapeutiques</td></tr></thead><tbody><tr><td><br></td><td><br></td><td><br></td><td><br></td><td><br></td></tr><tr><td><br></td><td><br></td><td><br></td><td><br></td><td><br></td></tr><tr><td><br></td><td><br></td><td><br></td><td><br></td><td><br></td></tr><tr><td><br></td><td><br></td><td><br></td><td><br></td><td><br></td></tr><tr><td><br></td><td><br></td><td><br></td><td><br></td><td><br></td></tr><tr><td><br></td><td><br></td><td><br></td><td><br></td><td><br></td></tr></tbody></table>", $line);
                $text[$nline] = $line;
            } elseif (strpos($line, 'TTT5 ') !== false) {
                $line = str_replace("TTT5 ", "", $line);
                $line = str_replace($line, "<table class='table table-bordered'><thead><tr><td>Médicament</td><td>Dose total /24h</td><td>Fréquence d'administration</td><td>Adaptation posologique proposée</td></tr></thead><tbody><tr><td><br></td><td><br></td><td><br></td><td><br></td></tr><tr><td><br></td><td><br></td><td><br></td><td><br></td></tr><tr><td><br></td><td><br></td><td><br></td><td><br></td></tr><tr><td><br></td><td><br></td><td><br></td><td><br></td></tr><tr><td><br></td><td><br></td><td><br></td><td><br></td></tr><tr><td><br></td><td><br></td><td><br></td><td><br></td></tr></tbody></table>", $line);
                $text[$nline] = $line;
            } elseif (strpos($line, 'TTT6 ') !== false) {
                $line = str_replace("TTT6 ", "", $line);
                $line = str_replace($line, "<table class='table table-bordered'><thead><tr><td>Médicament</td><td>Date et heure d'administration</td><td>Voie d'administration</td><td>Observation</td></tr></thead><tbody><tr><td><br></td><td><br></td><td><br></td><td><br></td></tr><tr><td><br></td><td><br></td><td><br></td><td><br></td></tr><tr><td><br></td><td><br></td><td><br></td><td><br></td></tr><tr><td><br></td><td><br></td><td><br></td><td><br></td></tr><tr><td><br></td><td><br></td><td><br></td><td><br></td></tr><tr><td><br></td><td><br></td><td><br></td><td><br></td></tr></tbody></table>", $line);
                $text[$nline] = $line;
            } elseif (strpos($line, 'TTT7 ') !== false) {
                $line = str_replace("TTT7 ", "", $line);
                $line = str_replace($line, "<table class='table table-bordered'><thead><tr><td>Surveiller les paramètres vitaux</td><td>Effets indésirables</td><td>Interaction médicamenteuse</td><td>Interaction Aliment/médicament</td></tr></thead><tbody><tr><td><br></td><td><br></td><td><br></td><td><br></td></tr><tr><td><br></td><td><br></td><td><br></td><td><br></td></tr><tr><td><br></td><td><br></td><td><br></td><td><br></td></tr><tr><td><br></td><td><br></td><td><br></td><td><br></td></tr><tr><td><br></td><td><br></td><td><br></td><td><br></td></tr><tr><td><br></td><td><br></td><td><br></td><td><br></td></tr><tr><td><br></td><td><br></td><td><br></td><td><br></td></tr></tbody></table>", $line);
                $text[$nline] = $line;
            } elseif (strpos($line, 'TTT8 ') !== false) {
                $line = str_replace("TTT8 ", "", $line);
                $line = str_replace($line, "<table class='table table-bordered'><tr><td>Patient Traité</td><td>Date de naissance</td><td>Il s’agit d’un nouveau-né, le traitement a été pris</td></tr><tr><td rowspan='2' colspan='1' >Initiales : </td><td rowspan='2' colspan='1' >Poids :</td><td>Par le Né : <input type='checkbox'/></td></tr><tr><td>Lors de l’allaitement : <input type='checkbox'/></td></tr><tr><td rowspan='2' colspan='1' >Sexe : F <input type='checkbox'/> H <input type='checkbox'/></td><td rowspan='2' colspan='1' >Taille :</td><td>Lors de la grossesse: <input type='checkbox'/></td></tr><tr><td>Trimestre de grossesse : <input type='checkbox'/> <br> Préciser le trimestre :</td></tr><tr><td rowspan='1' colspan='5' >Antécedents / facteurs favorisants :</td></tr></table>", $line);
                $text[$nline] = $line;
            } elseif (strpos($line, 'TTT9 ') !== false) {
                $line = str_replace("TTT9 ", "", $line);
                $line = str_replace($line, "<table class='table table-bordered'><thead><tr><td>Nom</td><td>Voie</td><td>Posologie</td><td>Début</td><td>Fin</td><td>Indication</td></tr></thead><tbody><tr><td><br></td><td><br></td><td><br></td><td><br></td><td><br></td><td><br></td></tr><tr><td><br></td><td><br></td><td><br></td><td><br></td><td><br></td><td><br></td></tr><tr><td><br></td><td><br></td><td><br></td><td><br></td><td><br></td><td><br></td></tr><tr><td><br></td><td><br></td><td><br></td><td><br></td><td><br></td><td><br></td></tr><tr><td><br></td><td><br></td><td><br></td><td><br></td><td><br></td><td><br></td></tr></tbody></table>", $line);
                $text[$nline] = $line;
            } elseif (strpos($line, 'TTT10 ') !== false) {
                $line = str_replace("TTT10 ", "", $line);
                $line = str_replace($line, "<table class='table table-bordered'><tbody><tr><td>Un ou des produits ont été arrêtés?<br><input type='checkbox'/> Sans information <input type='checkbox'/> Non <input type='checkbox'/> Oui  N⁰</td><td>Un des produits ont été réintroduit?<br><input type='checkbox'/> Sans information <input type='checkbox'/> Non <input type='checkbox'/> Oui  N⁰</td></tr><tr><td>Disparition de la réaction après arrêt<br><input type='checkbox'/> Sans information <input type='checkbox'/> Non <input type='checkbox'/> Oui  N⁰</td><td>Réapparition de la réaction après réintroduction<br><input type='checkbox'/> Sans information <input type='checkbox'/> Non <input type='checkbox'/> Oui  N⁰</td></tr></tbody></table>", $line);
                $text[$nline] = $line;
            } elseif (strpos($line, 'TTT11 ') !== false) {
                $line = str_replace("TTT11 ", "", $line);
                $line = str_replace($line, "<table class='table table-bordered'><thead><tr><td><b>Effet</b></td><td><b>Gravité</b></td><td><b>Evolution</b></td></tr></thead><tbody><tr><td>Date de survenu :<br>Duree de l'effet :<br>Description :</td><td><input type='checkbox'/> Hospitalisation ou prolongation d'hospitalisation<br><input type='checkbox'/> Invalidité permanente<br><input type='checkbox'/> Mise en jeu du pronostic vital<br><input type='checkbox'/> Décès</td><td><input type='checkbox'/> Guérison sans séquelles<br><input type='checkbox'/>Guérison avec séquelles<br><input type='checkbox'/> Décès dû à l'effet</span><br><input type='checkbox'/> Décès auquel l’effet a contribué</span><br></span><input type='checkbox'/> Décès sans rapport avec l'effet<br><input type='checkbox'/> Sujet non encore rétabli<br><input type='checkbox'/> Inconnue</td></tr></tbody></table>", $line);
                $text[$nline] = $line;
            } elseif (strpos($line, 'TTT12 ') !== false) {
                $line = str_replace("TTT12 ", "", $line);
                $line = str_replace($line, "<table class='table table-bordered'><tbody><tr><td>Nom du dispositif médical : (ex : cathéter sus pubien)</td><td>&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;</td></tr><tr><td>Dénomination commerciale ou usuelle (ex : cystocath)</td><td><br></td></tr><tr><td>Caractéristiques :<br>&nbsp; &nbsp; &nbsp; - Unités métriques<p style='line-height: 1;'>&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; <b>*</b>&nbsp;Charrière<p style='line-height: 1;'>&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;&nbsp;<span style='font-weight: 700;'>*</span>&nbsp;Gauge<p style='line-height: 1;'>&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;&nbsp;<span style='font-weight: 700;'>*</span>&nbsp;Dimensions<p style='line-height: 1;'>&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;&nbsp;<span style='font-weight: 700;'>*</span>&nbsp;Taille<br></td><td><br></td></tr><tr><td>&nbsp; &nbsp; &nbsp; - Procédé de stérilisation<br></td><td><br></td></tr><tr><td>&nbsp; &nbsp; &nbsp; - Conditionnement (individuel-collectif-kit)<br></td><td><br></td></tr><tr><td>&nbsp; &nbsp; &nbsp; - Date de fabrication/date de péremption<br></td><td><br></td></tr><tr><td>&nbsp; &nbsp; &nbsp; - N⁰ du lot<br></td><td><br></td></tr><tr><td>&nbsp; &nbsp; &nbsp; - N⁰ de référence du fabricant<br></td><td><br></td></tr><tr><td>&nbsp; &nbsp; &nbsp; - N⁰ de série pour la traçabilité des DM implantables<br></td><td><br></td></tr></tbody></table>", $line);
                $text[$nline] = $line;
            } elseif (strpos($line, 'TTT13 ') !== false) {
                $line = str_replace("TTT13 ", "", $line);
                $line = str_replace($line, "<table class='table table-bordered'><tbody><tr><td>En fonction de la dangerosité : (classe I, II ou III)</td><td>&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;</td></tr><tr><td>En fonction de l'abord</td><td><br></td></tr><tr><td>En fonction de la fabrication (en série, série limitée ou sur mesure)</td><td><br></td></tr></tbody></table>", $line);
                $text[$nline] = $line;
            } elseif (strpos($line, 'TTT14 ') !== false) {
                $line = str_replace("TTT14 ", "", $line);
                $line = str_replace($line, "<table class='table table-bordered'><thead><tr><td colspan='2'>L’émetteur du signalement</td></tr></thead><tbody><tr><td rowspan='1' colspan='2'>Nom, prénom :</td></tr><tr><td rowspan='1' colspan='2'>Qualité :</td></tr><tr><td rowspan='1' colspan='2'>Service :</td></tr><tr><td rowspan='1' colspan='2'>E-mail :</td></tr><tr><td>Tel :</td><td>Fax :</td></tr></tbody></table>", $line);
                $text[$nline] = $line;
            } elseif (strpos($line, 'TTT15 ') !== false) {
                $line = str_replace("TTT15 ", "", $line);
                $line = str_replace($line, "<table class='table table-bordered'><thead><tr><td colspan='2'>Le dispositif médical implique (DM)</td></tr></thead><tbody><tr><td rowspan='1' colspan='2'>Dénomination commune du DM :</td></tr><tr><td rowspan='1' colspan='2'>Dénomination commerciale : <br /> Modèle/type/référence</td></tr><tr><td rowspan='1' colspan='2'>Nom, adresse du fabricant :</td></tr><tr><td>Tel :</td><td>Fax :</td></tr><tr><td rowspan='1' colspan='2'>Nom, adresse du fournisseur (à remplir par la pharmacie)</td></tr><tr><td>Tel :</td><td>Fax :</td></tr></tbody></table>", $line);
                $text[$nline] = $line;
            } elseif (strpos($line, 'TTT16 ') !== false) {
                $line = str_replace("TTT16 ", "", $line);
                $line = str_replace($line, "<table class='table table-bordered'><tr><td>Date de survenue</td><td>Lieu de survenue</td><td colspan='3'></td></tr><tr><td rowspan='1' colspan='2'>Si nécessaire : nom, qualité, téléphone, fax de l’utilisateur à contacter :</td><td rowspan='1' colspan='3'>Conséquences cliniques constatées :</td></tr><tr><td rowspan='1' colspan='2'>Circonstances de survenue /description des faits :</td><td rowspan='1' colspan='3'>Mesures conservatoires et actions entreprise :</td></tr><tr><td>Situation de signalement : (à remplir par la pharmacie)</td><td></td><td>La fabrication ou le fournisseur est-il informé de l’incident ou du risque d’incident ?</td><td>Oui</td><td>Non</td></tr></table>", $line);
                $text[$nline] = $line;
            } elseif (strpos($line, 'TTT17 ') !== false) {
                $line = str_replace("TTT17 ", "", $line);
                $line = str_replace($line, "<table class='table table-bordered'><thead><tr><th>Médicament</th><th>Posologie</th><th>Données personnelles et cliniques du patient</th></tr></thead><tbody><tr><td></td><td></td><td rowspan='6' colspan='1'></td></tr><tr><td></td><td></td></tr><tr><td></td><td></td></tr><tr><td></td><td></td></tr><tr><td></td><td></td></tr><tr><td></td><td></td></tr></tbody></table>", $line);
                $text[$nline] = $line;
            } elseif (strpos($line, 'TTT18 ') !== false) {
                $line = str_replace("TTT18 ", "", $line);
                $line = str_replace($line, "<table class='table table-bordered'><thead><tr><th>Médicament</th><th>Indication</th><th>Conseil</th></tr></thead><tbody><tr><td></td><td></td><td></td></tr><tr><td></td><td></td><td></td></tr><tr><td></td><td></td><td></td></tr><tr><td></td><td></td><td></td></tr><tr><td></td><td></td><td></td></tr><tr><td></td><td></td><td></td></tr></tbody></table>", $line);
                $text[$nline] = $line;
            } elseif (strpos($line, 'TTT19 ') !== false) {
                $line = str_replace("TTT19 ", "", $line);
                $line = str_replace($line, "<table class='table table-bordered'><thead><tr><th>Composition</th><th>Mode de préparation</th><th>Indication</th></tr></thead><tbody><tr><td></td><td rowspan='6' colspan='1'></td>></td><td rowspan='6' colspan='1'></td></tr><tr><td></td></tr><tr><td></td></tr><tr><td></td></tr><tr><td></td></tr><tr><td></td></tr></tbody></table>", $line);
                $text[$nline] = $line;
            } elseif (strpos($line, 'TTT20 ') !== false) {
                $line = str_replace("TTT20 ", "", $line);
                $line = str_replace($line, '<table class="table table-bordered"><thead><tr><td rowspan="1" colspan="1"></td><th rowspan="1" colspan="32">Mobilité dentaire</th></tr></thead><tbody><tr><td rowspan="1" colspan="1"></td><td rowspan="1" colspan="2">18</td><td rowspan="1" colspan="2">17</td><td rowspan="1" colspan="2">16</td><td rowspan="1" colspan="2">15</td><td rowspan="1" colspan="2">14</td><td rowspan="1" colspan="2">13</td><td rowspan="1" colspan="2">12</td><td rowspan="1" colspan="2">11</td><td rowspan="1" colspan="2">21</td><td rowspan="1" colspan="2">22</td><td rowspan="1" colspan="2">23</td><td rowspan="1" colspan="2">24</td><td rowspan="1" colspan="2">25</td><td rowspan="1" colspan="2">26</td><td rowspan="1" colspan="2">27</td><td rowspan="1" colspan="2">28</td></tr><tr><td rowspan="1" colspan="1">EX</td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td></tr><tr><td rowspan="1" colspan="1">R1</td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td></tr><tr><td rowspan="1" colspan="1"></td><td rowspan="1" colspan="2">48</td><td rowspan="1" colspan="2">47</td><td rowspan="1" colspan="2">46</td><td rowspan="1" colspan="2">45</td><td rowspan="1" colspan="2">44</td><td rowspan="1" colspan="2">43</td><td rowspan="1" colspan="2">42</td><td rowspan="1" colspan="2">41</td><td rowspan="1" colspan="2">31</td><td rowspan="1" colspan="2">32</td><td rowspan="1" colspan="2">33</td><td rowspan="1" colspan="2">34</td><td rowspan="1" colspan="2">35</td><td rowspan="1" colspan="2">36</td><td rowspan="1" colspan="2">37</td><td rowspan="1" colspan="2">38</td></tr><tr><td rowspan="1" colspan="1">EX</td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td></tr><tr><td rowspan="1" colspan="1">R1</td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td></tr></tbody><thead><tr><td rowspan="1" colspan="1"></td><th rowspan="1" colspan="32">Atteintes de furcation</th></tr></thead><tbody><tr><td rowspan="1" colspan="1"></td><td rowspan="1" colspan="2">18</td><td rowspan="1" colspan="2">17</td><td rowspan="1" colspan="2">16</td><td rowspan="1" colspan="2">15</td><td rowspan="1" colspan="2">14</td><td rowspan="10" colspan="12"></td><td rowspan="1" colspan="2">24</td><td rowspan="1" colspan="2">25</td><td rowspan="1" colspan="2">26</td><td rowspan="1" colspan="2">27</td><td rowspan="1" colspan="2">28</td></tr><tr><td rowspan="2" colspan="1">EX</td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="2" colspan="1"></td><td rowspan="2" colspan="1"></td><td rowspan="2" colspan="1"></td><td rowspan="2" colspan="1"></td><td rowspan="2" colspan="1"></td><td rowspan="2" colspan="1"></td><td rowspan="2" colspan="1"></td><td rowspan="2" colspan="1"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td></tr><tr><td rowspan="1" colspan="1"></td><td rowspan="1" colspan="1"></td><td rowspan="1" colspan="1"></td><td rowspan="1" colspan="1"></td><td rowspan="1" colspan="1"></td><td rowspan="1" colspan="1"></td><td rowspan="1" colspan="1"></td><td rowspan="1" colspan="1"></td><td rowspan="1" colspan="1"></td><td rowspan="1" colspan="1"></td><td rowspan="1" colspan="1"></td><td rowspan="1" colspan="1"></td></tr><tr><td rowspan="2" colspan="1">R1</td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="2" colspan="1"></td><td rowspan="2" colspan="1"></td><td rowspan="2" colspan="1"></td><td rowspan="2" colspan="1"></td><td rowspan="2" colspan="1"></td><td rowspan="2" colspan="1"></td><td rowspan="2" colspan="1"></td><td rowspan="2" colspan="1"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td></tr><tr><td rowspan="1" colspan="1"></td><td rowspan="1" colspan="1"></td><td rowspan="1" colspan="1"></td><td rowspan="1" colspan="1"></td><td rowspan="1" colspan="1"></td><td rowspan="1" colspan="1"></td><td rowspan="1" colspan="1"></td><td rowspan="1" colspan="1"></td><td rowspan="1" colspan="1"></td><td rowspan="1" colspan="1"></td><td rowspan="1" colspan="1"></td><td rowspan="1" colspan="1"></td></tr><tr><td rowspan="1" colspan="1"></td><td rowspan="1" colspan="2">48</td><td rowspan="1" colspan="2">47</td><td rowspan="1" colspan="2">46</td><td rowspan="5" colspan="4"></td><td rowspan="5" colspan="4"></td><td rowspan="1" colspan="2">36</td><td rowspan="1" colspan="2">37</td><td rowspan="1" colspan="2">38</td></tr><tr> <td rowspan="2" colspan="1">EX</td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td></tr><tr><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td></tr><tr> <td rowspan="2" colspan="1">R1</td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td></tr><tr><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td></tr></tbody></table>', $line);
                $text[$nline] = $line;
            } elseif (strpos($line, 'TTT21 ') !== false) {
                $line = str_replace("TTT21 ", "", $line);
                $line = str_replace($line, '<table class="table table-bordered"><tr><td>DENTS<br />SUPERIEURS</td><td>18</td><td>17</td><td>16</td><td>15</td><td>14</td><td>13</td><td>12</td><td>11</td><td>21</td><td>22</td><td>23</td><td>24</td><td>25</td><td>26</td><td>27</td><td>28</td></tr><tr><td>INSPECTION</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr><tr><td>EX.RADIO</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr><tr><td>DIAGNOSTIC</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr><tr><td>DENTS<br />INFEREURES</td><td>48</td><td>47</td><td>46</td><td>45</td><td>44</td><td>43</td><td>42</td><td>41</td><td>31</td><td>32</td><td>33</td><td>34</td><td>35</td><td>36</td><td>37</td><td>38</td></tr><tr><td>INSPECTION</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr><tr><td>EX.RADIO</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr><tr><td>DIAGNOSTIC</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr></table>', $line);
                $text[$nline] = $line;
            } elseif (strpos($line, 'TTT22 ') !== false) {
                $line = str_replace("TTT22 ", "", $line);
                $line = str_replace($line, '<table class="table table-bordered"><tr><td>DENTS<br />SUPERIEURS</td><td></td><td></td><td>55</td><td>54</td><td>53</td><td>52</td><td>51</td><td>61</td><td>62</td><td>63</td><td>64</td><td>65</td></tr><tr><td>INSPECTION</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr><tr><td>EX.RADIO</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr><tr><td>DIAGNOSTIC</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr><tr><td>DENTS<br />INFEREURES</td><td></td><td></td><td>85</td><td>84</td><td>83</td><td>82</td><td>81</td><td>71</td><td>72</td><td>73</td><td>74</td><td>75</td></tr><tr><td>INSPECTION</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr><tr><td>EX.RADIO</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr><tr><td>DIAGNOSTIC</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr></table>', $line);
                $text[$nline] = $line;
            } elseif (strpos($line, 'TTT23 ') !== false) {
                $line = str_replace("TTT23 ", "", $line);
                $line = str_replace($line, '<table class="table table-bordered"><thead><tr><th>TYPE DE PROTHESE(S)</th><th>DATE-ACTES CLINIQUES REALISES</th><td rowspan="8"><img src="/assets/images/dentaire1.png" /></td></tr></thead><tbody><tr><td>Examen clinique <br /> Synthèse et proposition</td><td></td></tr><tr><td>Prothèse adjointe <br /> Empreinte primaire</td><td></td></tr><tr><td>Etude des modéles</td><td></td></tr><tr><td>Conception</td><td></td></tr><tr><td>PEI (Réglage PEI Remarginage Surfaçage)</td><td></td></tr><tr><td>Enregistrement(Maquettes,PO/DVO Transfert modèles Choix des dents)</td><td></td></tr><tr><td>Insertion équilibration Conseils</td><td></td></tr></tbody></table>', $line);
                $text[$nline] = $line;
            } elseif (strpos($line, 'TTT23 ') !== false) {
                $line = str_replace("TTT23 ", "", $line);
                $line = str_replace($line, '<table "table table-bordered"><thead><tr><th>PROTHESE CONJOINTE</th><th>DATE ET ACTES CLINIQUES REALISES</th><td rowspan="8"><img src="/assets/images/dentaire2.png" /></td></tr></thead><tbody><tr><td>Examen clinique <br /> Synthèse et proposition</td><td></td></tr><tr><td>Analyse des modéles</td><td></td></tr><tr><td>Préparation des piliers ou faux moignons</td><td></td></tr><tr><td>Empreinte définitive</td><td></td></tr><tr><td>Essayage et ajustage</td><td></td></tr><tr><td>Assemblage et équilibration</td><td></td></tr><tr><td>Maintenance</td><td></td></tr></tbody></table>', $line);
                $text[$nline] = $line;
            }
        }
        return $text;
    } 
    
    public function getModelTextDentaire($text)
    {
        foreach ($text as $nline => $line) {
            if (strpos($line, 'YYY ') !== false) {
                $line = str_replace("YYY ", "", $line);
                $line = str_replace($line, "<h3>" . $line . "</h3>", $line);
                $text[$nline] = $line;
            } elseif (strpos($line, '---- ') !== false) {
                $line = str_replace("---- ", "", $line);
                $text[$nline] = $line;
            } elseif (strpos($line, 'AAA ') !== false) {
                $line = str_replace("AAA ", "", $line);
                $line = str_replace($line, "<h4>" . $line . "</h4>", $line);
                $text[$nline] = $line;
            } elseif (strpos($line, 'SSS ') !== false) {
                $line = str_replace("SSS ", "", $line);
                $line = str_replace($line, "<b>" . $line . "</b>", $line);
                $text[$nline] = $line;
            } elseif (strpos($line, 'PPP ') !== false) {
                $line = str_replace("PPP ", "", $line);
                $line = str_replace($line, "</b>" . $line, $line);
                $text[$nline] = $line;
            } elseif (strpos($line, 'BBB ') !== false) {
                $line = str_replace("BBB ", "", $line);
                $line = str_replace($line, "<li>" . $line . "</li>", $line);
                $text[$nline] = $line;
            } elseif (strpos($line, 'CCC') !== false) {
                $line = str_replace("CCC", "Oui <input type='checkbox'/> Non <input type='checkbox'/>", $line);
                $text[$nline] = $line;
            } elseif (strpos($line, 'FFF') !== false) {
                $line = str_replace("FFF", "<input type='checkbox'/>&nbsp;", $line);
                $text[$nline] = $line;
            } elseif (strpos($line, 'MMM ') !== false) {
                $line = str_replace("MMM ", "", $line);
                $line = str_replace($line, "<h3>" . $line . "</h3>", $line);
                $text[$nline] = $line;
            } elseif (strpos($line, 'TTT20 ') !== false) {
                $line = str_replace("TTT20 ", "", $line);
                $line = str_replace($line, '<table class="table table-bordered"><thead><tr><td rowspan="1" colspan="1"></td><th rowspan="1" colspan="32">Mobilités dentaire</th></tr></thead><tbody><tr><td rowspan="1" colspan="1"></td><td rowspan="1" colspan="2">18</td><td rowspan="1" colspan="2">17</td><td rowspan="1" colspan="2">16</td><td rowspan="1" colspan="2">15</td><td rowspan="1" colspan="2">14</td><td rowspan="1" colspan="2">13</td><td rowspan="1" colspan="2">12</td><td rowspan="1" colspan="2">11</td><td rowspan="1" colspan="2">21</td><td rowspan="1" colspan="2">22</td><td rowspan="1" colspan="2">23</td><td rowspan="1" colspan="2">24</td><td rowspan="1" colspan="2">25</td><td rowspan="1" colspan="2">26</td><td rowspan="1" colspan="2">27</td><td rowspan="1" colspan="2">28</td></tr><tr><td rowspan="1" colspan="1">EX</td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td></tr><tr><td rowspan="1" colspan="1">R1</td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td></tr><tr><td rowspan="1" colspan="1"></td><td rowspan="1" colspan="2">48</td><td rowspan="1" colspan="2">47</td><td rowspan="1" colspan="2">46</td><td rowspan="1" colspan="2">45</td><td rowspan="1" colspan="2">44</td><td rowspan="1" colspan="2">43</td><td rowspan="1" colspan="2">42</td><td rowspan="1" colspan="2">41</td><td rowspan="1" colspan="2">31</td><td rowspan="1" colspan="2">32</td><td rowspan="1" colspan="2">33</td><td rowspan="1" colspan="2">34</td><td rowspan="1" colspan="2">35</td><td rowspan="1" colspan="2">36</td><td rowspan="1" colspan="2">37</td><td rowspan="1" colspan="2">38</td></tr><tr><td rowspan="1" colspan="1">EX</td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td></tr><tr><td rowspan="1" colspan="1">R1</td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td></tr></tbody><thead><tr><td rowspan="1" colspan="1"></td><th rowspan="1" colspan="32">Atteintes de furcation</th></tr></thead><tbody><tr><td rowspan="1" colspan="1"></td><td rowspan="1" colspan="2">18</td><td rowspan="1" colspan="2">17</td><td rowspan="1" colspan="2">16</td><td rowspan="1" colspan="2">15</td><td rowspan="1" colspan="2">14</td><td rowspan="10" colspan="12"></td><td rowspan="1" colspan="2">24</td><td rowspan="1" colspan="2">25</td><td rowspan="1" colspan="2">26</td><td rowspan="1" colspan="2">27</td><td rowspan="1" colspan="2">28</td></tr><tr><td rowspan="2" colspan="1">EX</td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="2" colspan="1"></td><td rowspan="2" colspan="1"></td><td rowspan="2" colspan="1"></td><td rowspan="2" colspan="1"></td><td rowspan="2" colspan="1"></td><td rowspan="2" colspan="1"></td><td rowspan="2" colspan="1"></td><td rowspan="2" colspan="1"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td></tr><tr><td rowspan="1" colspan="1"></td><td rowspan="1" colspan="1"></td><td rowspan="1" colspan="1"></td><td rowspan="1" colspan="1"></td><td rowspan="1" colspan="1"></td><td rowspan="1" colspan="1"></td><td rowspan="1" colspan="1"></td><td rowspan="1" colspan="1"></td><td rowspan="1" colspan="1"></td><td rowspan="1" colspan="1"></td><td rowspan="1" colspan="1"></td><td rowspan="1" colspan="1"></td></tr><tr><td rowspan="2" colspan="1">R1</td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="2" colspan="1"></td><td rowspan="2" colspan="1"></td><td rowspan="2" colspan="1"></td><td rowspan="2" colspan="1"></td><td rowspan="2" colspan="1"></td><td rowspan="2" colspan="1"></td><td rowspan="2" colspan="1"></td><td rowspan="2" colspan="1"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td></tr><tr><td rowspan="1" colspan="1"></td><td rowspan="1" colspan="1"></td><td rowspan="1" colspan="1"></td><td rowspan="1" colspan="1"></td><td rowspan="1" colspan="1"></td><td rowspan="1" colspan="1"></td><td rowspan="1" colspan="1"></td><td rowspan="1" colspan="1"></td><td rowspan="1" colspan="1"></td><td rowspan="1" colspan="1"></td><td rowspan="1" colspan="1"></td><td rowspan="1" colspan="1"></td></tr><tr><td rowspan="1" colspan="1"></td><td rowspan="1" colspan="2">48</td><td rowspan="1" colspan="2">47</td><td rowspan="1" colspan="2">46</td><td rowspan="5" colspan="4"></td><td rowspan="5" colspan="4"></td><td rowspan="1" colspan="2">36</td><td rowspan="1" colspan="2">37</td><td rowspan="1" colspan="2">38</td></tr><tr> <td rowspan="2" colspan="1">EX</td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td></tr><tr><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td></tr><tr> <td rowspan="2" colspan="1">R1</td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td></tr><tr><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td><td rowspan="1" colspan="2"></td></tr></tbody></table>', $line);
                $text[$nline] = $line;
            } elseif (strpos($line, 'TTT21 ') !== false) {
                $line = str_replace("TTT21 ", "", $line);
                $line = str_replace($line, '<table class="table table-bordered"><tr><td>DENTS<br />SUPERIEURS</td><td>18</td><td>17</td><td>16</td><td>15</td><td>14</td><td>13</td><td>12</td><td>11</td><td>21</td><td>22</td><td>23</td><td>24</td><td>25</td><td>26</td><td>27</td><td>28</td></tr><tr><td>INSPECTION</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr><tr><td>EX.RADIO</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr><tr><td>DIAGNOSTIC</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr><tr><td>DENTS<br />INFEREURES</td><td>48</td><td>47</td><td>46</td><td>45</td><td>44</td><td>43</td><td>42</td><td>41</td><td>31</td><td>32</td><td>33</td><td>34</td><td>35</td><td>36</td><td>37</td><td>38</td></tr><tr><td>INSPECTION</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr><tr><td>EX.RADIO</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr><tr><td>DIAGNOSTIC</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr></table>', $line);
                $text[$nline] = $line;
            } elseif (strpos($line, 'TTT22 ') !== false) {
                $line = str_replace("TTT22 ", "", $line);
                $line = str_replace($line, '<table class="table table-bordered"><tr><td>DENTS<br />SUPERIEURS</td><td></td><td></td><td>55</td><td>54</td><td>53</td><td>52</td><td>51</td><td>61</td><td>62</td><td>63</td><td>64</td><td>65</td></tr><tr><td>INSPECTION</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr><tr><td>EX.RADIO</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr><tr><td>DIAGNOSTIC</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr><tr><td>DENTS<br />INFEREURES</td><td></td><td></td><td>85</td><td>84</td><td>83</td><td>82</td><td>81</td><td>71</td><td>72</td><td>73</td><td>74</td><td>75</td></tr><tr><td>INSPECTION</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr><tr><td>EX.RADIO</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr><tr><td>DIAGNOSTIC</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr></table>', $line);
                $text[$nline] = $line;
            } elseif (strpos($line, 'TTT23 ') !== false) {
                $line = str_replace("TTT23 ", "", $line);
                $line = str_replace($line, '<table class="table table-bordered"><thead><tr><th>TYPE DE PROTHESE(S)</th><th>DATE-ACTES CLINIQUES REALISES</th></tr></thead><tbody><tr><td>Examen clinique <br /> Synthèse et proposition</td><td></td><td rowspan="8"><img class="img_dent" src="/assets/images/dentaire1.jpeg" /></td></tr><tr><td>Prothèse adjointe <br /> Empreinte primaire</td><td></td></tr><tr><td>Etude des modéles</td><td></td></tr><tr><td>Conception</td><td></td></tr><tr><td>PEI (Réglage PEI Remarginage Surfaçage)</td><td></td></tr><tr><td>Enregistrement(Maquettes,PO/DVP Transfert modèles Choix des dents)</td><td></td></tr><tr><td>Insertion équilibration Conseils</td><td></td></tr></tbody></table>', $line);
                $text[$nline] = $line;
            } elseif (strpos($line, 'TTT24 ') !== false) {
                $line = str_replace("TTT24 ", "", $line);
                $line = str_replace($line, '<table class="table table-bordered"><thead><tr><th>PROTHESE CONJOINTE</th><th>DATE ET ACTES CLINIQUES REALISES</th></tr></thead><tbody><tr><td>Examen clinique <br /> Synthèse diagnostique</td><td></td><td rowspan="8"><img class="img_dent" src="/assets/images/dentaire2.jpeg" /></td></tr><tr><td>Analyse des modéles</td><td></td></tr><tr><td>Préparation des piliers ou faux moignons</td><td></td></tr><tr><td>Empreinte définitive</td><td></td></tr><tr><td>Essayage et ajustage</td><td></td></tr><tr><td>Assemblage et équilibration</td><td></td></tr><tr><td>Maintenance</td><td></td></tr></tbody></table>', $line);
                $text[$nline] = $line;
            }
        }
        // dd($text);
        return $text;
    } 
}