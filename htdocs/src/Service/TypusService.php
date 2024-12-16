<?php declare(strict_types=1);

namespace App\Service;


use App\Entity\Jacq\Herbarinput\Specimens;
use Doctrine\ORM\EntityManagerInterface;

readonly class TypusService
{
    //TODO I had no power to fight with this any more, kept as is
    public function __construct(protected EntityManagerInterface $entityManager)
    {
    }


    public function makeTypus(int $specimenId): string
    {
        $text = '';

        $sql = "SELECT typus_lat, tg.genus,
             ta.author, ta1.author author1, ta2.author author2, ta3.author author3,
             ta4.author author4, ta5.author author5,
             te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3,
             te4.epithet epithet4, te5.epithet epithet5,
             ts.synID, ts.taxonID, ts.statusID, tst.typified_by_Person, tst.typified_Date
            FROM (tbl_specimens_types tst, tbl_typi tt, tbl_tax_species ts)
             LEFT JOIN tbl_tax_authors ta ON ta.authorID=ts.authorID
             LEFT JOIN tbl_tax_authors ta1 ON ta1.authorID=ts.subspecies_authorID
             LEFT JOIN tbl_tax_authors ta2 ON ta2.authorID=ts.variety_authorID
             LEFT JOIN tbl_tax_authors ta3 ON ta3.authorID=ts.subvariety_authorID
             LEFT JOIN tbl_tax_authors ta4 ON ta4.authorID=ts.forma_authorID
             LEFT JOIN tbl_tax_authors ta5 ON ta5.authorID=ts.subforma_authorID
             LEFT JOIN tbl_tax_epithets te ON te.epithetID=ts.speciesID
             LEFT JOIN tbl_tax_epithets te1 ON te1.epithetID=ts.subspeciesID
             LEFT JOIN tbl_tax_epithets te2 ON te2.epithetID=ts.varietyID
             LEFT JOIN tbl_tax_epithets te3 ON te3.epithetID=ts.subvarietyID
             LEFT JOIN tbl_tax_epithets te4 ON te4.epithetID=ts.formaID
             LEFT JOIN tbl_tax_epithets te5 ON te5.epithetID=ts.subformaID
             LEFT JOIN tbl_tax_genera tg ON tg.genID=ts.genID
            WHERE tst.typusID=tt.typusID
             AND tst.taxonID=ts.taxonID
             AND specimenID=:specimen ORDER by tst.typified_Date DESC";
        $result = $this->entityManager->getConnection()->executeQuery($sql, ['specimen' => $specimenId]);

        while ($row = $result->fetchAssociative()) {
            if ($row['synID']) {
                $sql3 = "SELECT ts.statusID, ts.taxonID, tg.genus,
                      ta.author, ta1.author author1, ta2.author author2, ta3.author author3,
                      ta4.author author4, ta5.author author5,
                      te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3,
                      te4.epithet epithet4, te5.epithet epithet5
                     FROM tbl_tax_species ts
                      LEFT JOIN tbl_tax_authors ta ON ta.authorID=ts.authorID
                      LEFT JOIN tbl_tax_authors ta1 ON ta1.authorID=ts.subspecies_authorID
                      LEFT JOIN tbl_tax_authors ta2 ON ta2.authorID=ts.variety_authorID
                      LEFT JOIN tbl_tax_authors ta3 ON ta3.authorID=ts.subvariety_authorID
                      LEFT JOIN tbl_tax_authors ta4 ON ta4.authorID=ts.forma_authorID
                      LEFT JOIN tbl_tax_authors ta5 ON ta5.authorID=ts.subforma_authorID
                      LEFT JOIN tbl_tax_epithets te ON te.epithetID=ts.speciesID
                      LEFT JOIN tbl_tax_epithets te1 ON te1.epithetID=ts.subspeciesID
                      LEFT JOIN tbl_tax_epithets te2 ON te2.epithetID=ts.varietyID
                      LEFT JOIN tbl_tax_epithets te3 ON te3.epithetID=ts.subvarietyID
                      LEFT JOIN tbl_tax_epithets te4 ON te4.epithetID=ts.formaID
                      LEFT JOIN tbl_tax_epithets te5 ON te5.epithetID=ts.subformaID
                      LEFT JOIN tbl_tax_genera tg ON tg.genID=ts.genID
                     WHERE taxonID= :synonym";
                $result3 = $this->entityManager->getConnection()->executeQuery($sql3, ['synonym' => $row['synID']]);
                $row3 = $result3->fetchAssociative();
                $accName = $this->taxonWithHybrids($row3);
            } else {
                $accName = "";
            }

            $sql2 = "SELECT l.suptitel, la.autor, l.periodicalID, lp.periodical, l.vol, l.part, ti.paginae, ti.figures, l.jahr
                 FROM tbl_tax_index ti
                  INNER JOIN tbl_lit l ON l.citationID=ti.citationID
                  LEFT JOIN tbl_lit_periodicals lp ON lp.periodicalID=l.periodicalID
                  LEFT JOIN tbl_lit_authors la ON la.autorID=l.editorsID
                 WHERE ti.taxonID=:taxon";
            $result2 = $this->entityManager->getConnection()->executeQuery($sql2, ['taxon' => $row['taxonID']]);

            $text .= "<tr>"
                . "<td nowrap align=\"right\">" . $row['typus_lat'] . " of&nbsp;</td>"
                . "<td><b>" . $this->taxonWithHybrids($row) . "</b></td>"
                . "</tr>";
            while ($row2 = $result2->fetchAssociative()) {
                $text .= "<tr>"
                    . "<td></td>"
                    . "<td><b>" . $this->protolog($row2) . "</b></td>"
                    . "</tr>";
            }
            $text .= "<tr>"
                . "<td nowrap align=\"right\"></td>"
                . "<td>Typified by:&nbsp;<b>" . $row['typified_by_Person'] . "&nbsp;" . $row['typified_Date'] . "</b></td>"
                . "</tr>";
            if (strlen($accName) > 0) {
                $text .= "<tr>"
                    . "<td></td>"
                    . "<td><b>Current Name: <i>$accName</i></b></td>"
                    . "</tr>";
            }
        }
        return $text;
    }

    public function taxonWithHybrids($row): string
    {
        if ($row['statusID'] == 1 && strlen((string)$row['epithet']) == 0 && strlen((string)$row['author']) == 0) {
            $sql = "SELECT parent_1_ID, parent_2_ID
                        FROM tbl_tax_hybrids
                        WHERE taxon_ID_fk = :taxon";
            $rowHybrid = $this->entityManager->getConnection()->executeQuery($sql, ['taxon' => $row['taxonID']])->fetchAssociative();
            $sql1 = "SELECT tg.genus,
                                    ta.author, ta1.author author1, ta2.author author2, ta3.author author3,
                                    ta4.author author4, ta5.author author5,
                                    te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3,
                                    te4.epithet epithet4, te5.epithet epithet5
                                   FROM tbl_tax_species ts
                                    LEFT JOIN tbl_tax_authors ta ON ta.authorID = ts.authorID
                                    LEFT JOIN tbl_tax_authors ta1 ON ta1.authorID = ts.subspecies_authorID
                                    LEFT JOIN tbl_tax_authors ta2 ON ta2.authorID = ts.variety_authorID
                                    LEFT JOIN tbl_tax_authors ta3 ON ta3.authorID = ts.subvariety_authorID
                                    LEFT JOIN tbl_tax_authors ta4 ON ta4.authorID = ts.forma_authorID
                                    LEFT JOIN tbl_tax_authors ta5 ON ta5.authorID = ts.subforma_authorID
                                    LEFT JOIN tbl_tax_epithets te ON te.epithetID = ts.speciesID
                                    LEFT JOIN tbl_tax_epithets te1 ON te1.epithetID = ts.subspeciesID
                                    LEFT JOIN tbl_tax_epithets te2 ON te2.epithetID = ts.varietyID
                                    LEFT JOIN tbl_tax_epithets te3 ON te3.epithetID = ts.subvarietyID
                                    LEFT JOIN tbl_tax_epithets te4 ON te4.epithetID = ts.formaID
                                    LEFT JOIN tbl_tax_epithets te5 ON te5.epithetID = ts.subformaID
                                    LEFT JOIN tbl_tax_genera tg ON tg.genID = ts.genID
                                   WHERE taxonID = :parent";
            $row1 = $this->entityManager->getConnection()->executeQuery($sql1, ['parent' => $rowHybrid['parent_1_ID']])->fetchAssociative();

            $sql2 = "SELECT tg.genus,
                                    ta.author, ta1.author author1, ta2.author author2, ta3.author author3,
                                    ta4.author author4, ta5.author author5,
                                    te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3,
                                    te4.epithet epithet4, te5.epithet epithet5
                                   FROM tbl_tax_species ts
                                    LEFT JOIN tbl_tax_authors ta ON ta.authorID = ts.authorID
                                    LEFT JOIN tbl_tax_authors ta1 ON ta1.authorID = ts.subspecies_authorID
                                    LEFT JOIN tbl_tax_authors ta2 ON ta2.authorID = ts.variety_authorID
                                    LEFT JOIN tbl_tax_authors ta3 ON ta3.authorID = ts.subvariety_authorID
                                    LEFT JOIN tbl_tax_authors ta4 ON ta4.authorID = ts.forma_authorID
                                    LEFT JOIN tbl_tax_authors ta5 ON ta5.authorID = ts.subforma_authorID
                                    LEFT JOIN tbl_tax_epithets te ON te.epithetID = ts.speciesID
                                    LEFT JOIN tbl_tax_epithets te1 ON te1.epithetID = ts.subspeciesID
                                    LEFT JOIN tbl_tax_epithets te2 ON te2.epithetID = ts.varietyID
                                    LEFT JOIN tbl_tax_epithets te3 ON te3.epithetID = ts.subvarietyID
                                    LEFT JOIN tbl_tax_epithets te4 ON te4.epithetID = ts.formaID
                                    LEFT JOIN tbl_tax_epithets te5 ON te5.epithetID = ts.subformaID
                                    LEFT JOIN tbl_tax_genera tg ON tg.genID = ts.genID
                                   WHERE taxonID = :parent2";
            $row2 = $this->entityManager->getConnection()->executeQuery($sql2, ['parent2' => $rowHybrid['parent_2_ID']])->fetchAssociative();

            return $this->taxon($row1) . " x " . $this->taxon($row2);
        }

        return $this->taxon($row);

    }

    protected function taxon($row)
    {
        $text = $row['genus'] ?? '';
        if (!empty($row['epithet'])) {
            $text .= " " . $row['epithet'] . " " . $row['author'];
        }
        if (!empty($row['epithet1'])) {
            $text .= " subsp. " . $row['epithet1'] . " " . $row['author1'];
        }
        if (!empty($row['epithet2'])) {
            $text .= " var. " . $row['epithet2'] . " " . $row['author2'];
        }
        if (!empty($row['epithet3'])) {
            $text .= " subvar. " . $row['epithet3'] . " " . $row['author3'];
        }
        if (!empty($row['epithet4'])) {
            $text .= " forma " . $row['epithet4'] . " " . $row['author4'];
        }
        if (!empty($row['epithet5'])) {
            $text .= " subforma " . $row['epithet5'] . " " . $row['author5'];
        }

        return $text;
    }

    public function protolog($row): string
    {
        $text = "";
        if ($row['suptitel']) {
            $text .= "in " . $row['autor'] . ": " . $row['suptitel'] . " ";
        }
        if ($row['periodicalID']) {
            $text .= $row['periodical'];
        }
        $text .= " " . $row['vol'];
        if ($row['part']) {
            $text .= " (" . $row['part'] . ")";
        }
        $text .= ": " . $row['paginae'];
        if ($row['figures']) {
            $text .= "; " . $row['figures'];
        }
        $text .= " (" . $row['jahr'] . ")";

        return $text;
    }

    public function taxonName(Specimens $specimen): string
    {
        return $this->taxonWithHybrids($this->giantQueryForEveryone($specimen->getId()));
    }

    protected function giantQueryForEveryone(int $specimenId): array
    {
        $sql = "SELECT s.specimen_ID, tg.genus, c.Sammler, c.SammlerID, c.HUH_ID, c.VIAF_ID, c.WIKIDATA_ID,c.ORCID, c2.Sammler_2, ss.series, s.series_number,
                             s.Nummer, s.alt_number, s.Datum, s.Fundort, s.det, s.taxon_alt, s.Bemerkungen, s.typified, s.typusID,
                             s.digital_image, s.digital_image_obs, s.HerbNummer, s.CollNummer, s.ncbi_accession, s.observation,
                             s.Coord_W, s.W_Min, s.W_Sec, s.Coord_N, s.N_Min, s.N_Sec,
                             s.Coord_S, s.S_Min, s.S_Sec, s.Coord_E, s.E_Min, s.E_Sec, s.habitat, s.habitus, s.altitude_min, s.altitude_max,
                             n.nation_engl, p.provinz, s.Fundort, tf.family, tsc.cat_description, s.taxonID taxid,
                             mc.collection, mc.collectionID, mc.source_id, mc.coll_short, mc.coll_gbif_pilot,
                             m.source_code, m.source_name,
                             tid.imgserver_type, tid.imgserver_IP, tid.iiif_capable, tid.iiif_url, tid.HerbNummerNrDigits,
                             ta.author, ta1.author author1, ta2.author author2, ta3.author author3, ta4.author author4, ta5.author author5,
                             te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3, te4.epithet epithet4, te5.epithet epithet5,
                             ts.synID, ts.taxonID, ts.statusID
                            FROM tbl_specimens s
                             LEFT JOIN tbl_specimens_series ss           ON ss.seriesID = s.seriesID
                             LEFT JOIN tbl_management_collections mc     ON mc.collectionID = s.collectionID
                             LEFT JOIN meta m                            ON m.source_id = mc.source_id
                             LEFT JOIN tbl_img_definition tid            ON tid.source_id_fk = mc.source_id
                             LEFT JOIN tbl_geo_nation n                  ON n.NationID = s.NationID
                             LEFT JOIN tbl_geo_province p                ON p.provinceID = s.provinceID
                             LEFT JOIN tbl_collector c                   ON c.SammlerID = s.SammlerID
                             LEFT JOIN tbl_collector_2 c2                ON c2.Sammler_2ID = s.Sammler_2ID
                             LEFT JOIN tbl_tax_species ts                ON ts.taxonID = s.taxonID
                             LEFT JOIN tbl_tax_authors ta                ON ta.authorID = ts.authorID
                             LEFT JOIN tbl_tax_authors ta1               ON ta1.authorID = ts.subspecies_authorID
                             LEFT JOIN tbl_tax_authors ta2               ON ta2.authorID = ts.variety_authorID
                             LEFT JOIN tbl_tax_authors ta3               ON ta3.authorID = ts.subvariety_authorID
                             LEFT JOIN tbl_tax_authors ta4               ON ta4.authorID = ts.forma_authorID
                             LEFT JOIN tbl_tax_authors ta5               ON ta5.authorID = ts.subforma_authorID
                             LEFT JOIN tbl_tax_epithets te               ON te.epithetID = ts.speciesID
                             LEFT JOIN tbl_tax_epithets te1              ON te1.epithetID = ts.subspeciesID
                             LEFT JOIN tbl_tax_epithets te2              ON te2.epithetID = ts.varietyID
                             LEFT JOIN tbl_tax_epithets te3              ON te3.epithetID = ts.subvarietyID
                             LEFT JOIN tbl_tax_epithets te4              ON te4.epithetID = ts.formaID
                             LEFT JOIN tbl_tax_epithets te5              ON te5.epithetID = ts.subformaID
                             LEFT JOIN tbl_tax_genera tg                 ON tg.genID = ts.genID
                             LEFT JOIN tbl_tax_families tf               ON tf.familyID = tg.familyID
                             LEFT JOIN tbl_tax_systematic_categories tsc ON tf.categoryID = tsc.categoryID
                            WHERE s.accessible != '0'
                    AND s.specimen_ID = :specimen ";
        return $this->entityManager->getConnection()->executeQuery($sql, ['specimen' => $specimenId])->fetchAssociative();
    }

    public function taxonAuth(Specimens $specimen): string
    {
        $row = $this->giantQueryForEveryone($specimen->getId());
        if (!empty($specimen['digital_image']) || !empty($specimen['digital_image_obs'])) {
            $phaidra = false;
            if ($specimen['source_id'] == '1') {
                // for now, special treatment for phaidra is needed when wu has images
                $output['phaidraUrl'] = "";

                // ask phaidra server if it has the desired picture. If not, use old method
                $picname = sprintf("WU%0" . $specimen['HerbNummerNrDigits'] . ".0f", str_replace('-', '', $specimen['HerbNummer']));
                $ch = curl_init("https://app05a.phaidra.org/viewer/" . $picname);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $curl_response = curl_exec($ch);
                if ($curl_response) {
                    $info = curl_getinfo($ch);
                    if ($info['http_code'] == 200) {
                        $phaidra = true;
                        $output['phaidraUrl'] = $specimen['iiif_url'] . '?manifest=' . $config->get('JACQ_SERVICES') . 'iiif/manifest/' . $specimen['specimen_ID'];
                        $ch2 = curl_init($config->get('JACQ_SERVICES') . "iiif/manifest/" . $specimen['specimen_ID']);
                        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
                        $curl_response2 = curl_exec($ch2);
                        curl_close($ch2);
                        $decoded = json_decode($curl_response2, true);
                        $output['phaidraThumbs'] = array();
                        foreach ($decoded['sequences'] as $sequence) {
                            foreach ($sequence['canvases'] as $canvas) {
                                foreach ($canvas['images'] as $image) {
                                    $output['phaidraThumbs'][] = array('img' => $image['resource']['service']['@id'],
                                        'viewer' => $output['phaidraUrl'],
                                        'file' => $picname);
                                }
                            }
                        }
                    }
                }
                curl_close($ch);
            }
            if ($phaidra) {  // phaidra picture found
                $output['picture_include'] = 'templates/detail_inc_phaidra.php';
//        include 'templates/detail_base.php';
//        include 'templates/detail_phaidra.php';  // just needed for testing
            } elseif ($specimen['iiif_capable']) {
                $ch = curl_init($config->get('JACQ_SERVICES') . "iiif/manifestUri/" . $specimen['specimen_ID']);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $curl_response = curl_exec($ch);
                if ($curl_response !== false) {
                    $curl_result = json_decode($curl_response, true);
                    $output['manifest'] = $curl_result['uri'];
                } else {
                    $output['manifest'] = "";
                }
                curl_close($ch);
                $output['picture_include'] = 'templates/detail_inc_iiif.php';
            } elseif ($specimen['imgserver_type'] == 'bgbm') {  // but not iiif_capable
                $output['bgbm_options'] = '?filename=' . rawurlencode(basename($specimen['specimen_ID'])) . '&sid=' . $specimen['specimen_ID'];
                $output['picture_include'] = 'templates/detail_inc_bgbm.php';
                //    'baku' is depricated and no loner used
                //    } elseif ($specimen['imgserver_type'] == 'baku') {
                //        $options = 'filename=' . rawurlencode(basename($specimen['specimen_ID'])) . '&sid=' . $specimen['specimen_ID'];
                //        echo "<td valign='top' align='center'>"
                //           . "<a href='image.php?{$options}&method=show' target='imgBrowser'><img src='image.php?{$options}&method=thumb border='2'></a><br>"
                //           . "(<a href='image.php?{$options}&method=show' target='imgBrowser'>Open viewer</a>)"
                //           . "</td>";
            } elseif ($specimen['imgserver_type'] == 'djatoka') {   // but not iiif_capable, so the original one
                $picdetails = getPicDetails($specimen['specimen_ID']);
                $transfer = getPicInfo($picdetails);
                $output['djatoka_options'] = array();
                if ($transfer) {
                    if (!empty($transfer['error'])) {
                        $output['djatoka']['error'] = "Picture server list error. Falling back to original image name";
                        $output['djatoka_options'][] = 'filename=' . rawurlencode(basename($picdetails['filename'])) . '&sid=' . $specimen['specimen_ID'];
                        error_log($transfer['error']);
                    } else {
                        if (count($transfer['pics'] ?? array()) > 0) {
                            foreach ($transfer['pics'] as $v) {
                                $output['djatoka_options'][] = 'filename=' . rawurlencode(basename($v)) . '&sid=' . $specimen['specimen_ID'];
                            }
                            $output['djatoka']['error'] = "";
                        } else {
                            $output['djatoka']['error'] = "no pictures found";
                        }
                        if (trim($transfer['output'])) {
                            $output['djatoka_transfer_output'] = "\n" . $transfer['output'] . "\n";
                        }
                    }
                } else {
                    $output['djatoka']['error'] = "transmission error";
                }
                return 'templates/detail_inc_djatoka.php';
            } else {
                return 'templates/detail_inc_noPictures.php';
            }
        }
        return '';
    }
}
