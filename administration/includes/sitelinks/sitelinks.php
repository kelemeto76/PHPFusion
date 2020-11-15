<?php
/*-------------------------------------------------------+
| PHP-Fusion Content Management System
| Copyright (C) PHP-Fusion Inc
| https://www.phpfusion.com/
+--------------------------------------------------------+
| Filename: sitelinks.php
| Author: Core Development Team (coredevs@phpfusion.com)
+--------------------------------------------------------+
| This program is released as free software under the
| Affero GPL license. You can redistribute it and/or
| modify it under the terms of this license which you
| can read by viewing the included agpl.txt or online
| at www.gnu.org/licenses/agpl.html. Removal of this
| copyright header is strictly prohibited without
| written permission from the original author(s).
+--------------------------------------------------------*/
(defined("IN_FUSION") || exit);

function display_sitelinks() {

    if (iADMIN && checkrights("SL")) {

        $aidlink = fusion_get_aidlink();

        ini_set("memory_limit", "4000000000");

        require_once INCLUDES."theme_functions_include.php";

        $rowstart = (int)(post("rowstart", FILTER_VALIDATE_INT) ?: 0);

        $limit = (int)(post("length", FILTER_VALIDATE_INT) ?: 36);

        $link_cat = (int)(get("cat", FILTER_VALIDATE_INT) ?: 0);

        $refs = (int)(get("refs", FILTER_VALIDATE_INT) ?: 0);

        $search = post(["search", "value"]);

        $columns = [
            "sl.link_name LIKE '$search%'",
        ];

        $orderby = "ORDER BY sl.link_order";
        $ordering = [];
        if (!empty($_POST["order"])) {
            foreach ($_POST["order"] as $order) {
                $column_index = $order["column"];
                if (!$column_index or $column_index == 1) {
                    $column_index = 6;
                }
                if ($column_name = post(["columns", $column_index, "data"])) {
                    $ordering[] = form_sanitizer($column_name)." ".form_sanitizer($order["dir"]);
                }
            }
            $orderby = "ORDER BY ".implode(",", $ordering);
        }

        $link_cat_sql = (!$search ? "sl.link_cat='$link_cat' AND " : "");

        $count_cond = $link_cat_sql." sl.link_position=$refs";
        $sql_cond = "WHERE ".$link_cat_sql." sl.link_position=$refs";

        if ($refs == 1) {
            $sql_cond = "WHERE ".$link_cat_sql." (sl.link_position=1 OR sl.link_position=2)";
        }

        $table = DB_SITE_LINKS." sl LEFT JOIN ".DB_SITE_LINKS." sl2 ON (sl2.link_cat=sl.link_id)";
        $count_sel = "(sl.link_id)";
        $column_sel = "sl.*, count(sl2.link_id) 'link_count'";
        if ($search) {
            $count_cond = "(".implode(" OR ", $columns).")";
            $sql_cond .= " AND $count_cond";
        }

        $rowsearch = "LIMIT $rowstart, $limit";

        $list = [];

        $max_rows = dbcount($count_sel, $table, $count_cond);

        $sql_query = "SELECT ".$column_sel." FROM ".$table.whitespace($sql_cond)." GROUP BY sl.link_id".whitespace($orderby).whitespace($rowsearch);

        $result = dbquery($sql_query);
        if ($rows = dbrows($result)) {

            $i = 1;

            while ($data = dbarray($result)) {
                //print_p($data);

                // automatic link order repair
                $link_order = $data["link_order"];
                if (isset($ordering) && in_array("link_order asc", $ordering)) {
                    if ($data["link_order"] !== $i) {
                        dbquery("UPDATE ".DB_SITE_LINKS." SET link_order=:oid WHERE link_id=:lid", [":lid" => $data["link_id"], ":oid" => $i]);
                        $link_order = $i;
                    }
                }

                $admin_links = "<a class='text-muted' href='".$data["link_url"]."' target='_blank'>View</a> - ";
                $admin_links .= "<a class='text-muted' href='".ADMIN."site_links.php".$aidlink."&refs=form&action=edit&id=".$data["link_id"]."'>Edit</a> - ";
                $admin_links .= "<a class='text-danger del-warn' href='".ADMIN."site_links.php".$aidlink."&refs=form&action=del&&id=".$data["link_id"]."'>Remove</a>";

                $link_icon = "";
                if ($data["link_icon"]) {
                    $link_icon = "<div style='display:inline;width:30px;height:30px;margin-right:5px;'><i class='".$data["link_icon"]."'></i></div>";
                }

                $link_name = $data["link_name"];
                if ($data["link_count"]) {
                    $link_name = "<a href='".ADMIN."site_links.php".$aidlink."&section=links&refs=".$refs."&cat=".$data["link_id"]."'>".$data["link_name"]."</a>";
                }

                //https://main.test/administration/site_links.php??aid=2af8083961791c57&section=links&refs=&cat=0
                //".form_checkbox("link_id[]", "", '', ["input_id" => "link_id-".$data["link_id"], "value" => $data['link_id'], "class" => 'm-0'])."
                $list[] = [
                    "DT_RowId"        => $data["link_id"],
                    "link_checkbox"   => "<div class='display-flex-row'>
                    <div>".form_checkbox("link_id[]", "", "", ["value"=>$data["link_id"]])."</div><div><i class='pointer handle fa fa-arrows spacer-xs'></i></div>
                    </div>",
                    "link_name"       => "<div class='display-flex-row'>$link_icon<div><strong>$link_name</strong><br/>ID:".$data["link_id"]." | $admin_links</div></div>",
                    "link_count"      => format_num($data["link_count"]),
                    "link_status"     => $data["link_status"] ? "Published" : "Draft",
                    "link_window"     => $data["link_window"] ? "<i class='fas fa-check'></i>" : "<i class='fas fa-times-circle'></i>",
                    "link_visibility" => getgroupname($data["link_visibility"]),
                    "link_order"      => "<span class='num'>".$link_order."</span>"
                ];

                $i++;
            }
        }

        //require_once INCLUDES."ajax_include.php";
        //fusion_set_header_type("json");
        echo json_encode(["data" => $list, "recordsTotal" => $rows, "recordsFiltered" => $max_rows, "responsive" => TRUE]);

    } else {
        die("Not authorized to view the information");
    }

}

fusion_add_hook("fusion_admin_hooks", "display_sitelinks");
