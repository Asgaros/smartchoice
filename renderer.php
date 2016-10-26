<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

class mod_smartchoice_renderer extends plugin_renderer_base {
    // Returns HTML to display choices of option
    public function display_options($options, $coursemoduleid, $multiple = false) {
        $target = new moodle_url('/mod/smartchoice/view.php');
        $attributes = array('method'=>'POST', 'action'=>$target, 'class'=> 'vertical');

        $html = html_writer::start_tag('form', $attributes);
        $html .= html_writer::start_tag('ul', array('class'=>'choices' ));

        $choicecount = 0;
        foreach ($options['options'] as $option) {
            $choicecount++;
            $html .= html_writer::start_tag('li', array('class'=>'option'));
            if ($multiple) {
                $option->attributes->name = 'answer[]';
                $option->attributes->type = 'checkbox';
            } else {
                $option->attributes->name = 'answer';
                $option->attributes->type = 'radio';
            }
            $option->attributes->id = 'choice_'.$choicecount;

            $labeltext = $option->text;

            $html .= html_writer::empty_tag('input', (array)$option->attributes);
            $html .= html_writer::tag('label', $labeltext, array('for'=>$option->attributes->id));
            $html .= html_writer::end_tag('li');
        }
        $html .= html_writer::tag('li','', array('class'=>'clearfloat'));
        $html .= html_writer::end_tag('ul');
        $html .= html_writer::tag('div', '', array('class'=>'clearfloat'));
        $html .= html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'sesskey', 'value'=>sesskey()));
        $html .= html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'action', 'value'=>'makechoice'));
        $html .= html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'id', 'value'=>$coursemoduleid));
        $html .= html_writer::empty_tag('input', array('type' => 'submit', 'value' => get_string('savemychoice', 'smartchoice'), 'class' => 'button'));
        $html .= html_writer::end_tag('ul');
        $html .= html_writer::end_tag('form');

        return $html;
    }

    // Returns HTML to display choices result
    public function display_publish_anonymous_vertical($choices) {
        if (empty($choices)) {
            return;
        }

        $html = '';
        $table = new html_table();
        $table->cellpadding = 5;
        $table->cellspacing = 0;
        $table->attributes['class'] = 'results anonymous ';
        $table->summary = get_string('responsesto', 'smartchoice', format_string($choices->name));
        $table->data = array();

        $count = 0;
        ksort($choices->options);
        $rows = array();

        $headercelldefault = new html_table_cell();
        $headercelldefault->scope = 'row';
        $headercelldefault->header = true;
        $headercelldefault->attributes = array('class'=>'header data');

        // column header
        $tableheader = clone($headercelldefault);
        $tableheader->text = html_writer::tag('div', get_string('choiceoptions', 'smartchoice'), array('class' => 'accesshide'));
        $rows['header'][] = $tableheader;

        // graph row header
        $graphheader = clone($headercelldefault);
        $graphheader->text = html_writer::tag('div', get_string('responsesresultgraphheader', 'smartchoice'), array('class' => 'accesshide'));
        $rows['graph'][] = $graphheader;

        // user number row header
        $usernumberheader = clone($headercelldefault);
        $usernumberheader->text = get_string('numberofvotes', 'smartchoice');
        $rows['usernumber'][] = $usernumberheader;

        // user percentage row header
        $userpercentageheader = clone($headercelldefault);
        $userpercentageheader->text = get_string('numberofvotesinpercentage', 'smartchoice');
        $rows['userpercentage'][] = $userpercentageheader;

        $contentcelldefault = new html_table_cell();
        $contentcelldefault->attributes = array('class'=>'data');

        foreach ($choices->options as $optionid => $option) {
            // calculate display length
            $height = $percentageamount = $numberofvotes = 0;
            $usernumber = $userpercentage = '';

            if (!empty($option->user)) {
               $numberofvotes = count($option->user);
            }

            if($choices->numberofvotes > 0) {
               $height = (300 * ((float)$numberofvotes / (float)$choices->numberofvotes));
               $percentageamount = ((float)$numberofvotes/(float)$choices->numberofvotes)*100.0;
            }

            $displaygraph = html_writer::tag('img','', array('style'=>'height:'.$height.'px;width:49px;', 'alt'=>'', 'src'=>$this->output->pix_url('column', 'smartchoice')));

            // header
            $headercell = clone($contentcelldefault);
            $headercell->text = $option->text;
            $rows['header'][] = $headercell;

            // Graph
            $graphcell = clone($contentcelldefault);
            $graphcell->attributes = array('class'=>'graph vertical data');
            $graphcell->text = $displaygraph;
            $rows['graph'][] = $graphcell;

            $usernumber .= html_writer::tag('div', ' '.$numberofvotes.'', array('class'=>'numberofvotes', 'title'=> get_string('numberofvotes', 'smartchoice')));
            $userpercentage .= html_writer::tag('div', format_float($percentageamount,1). '%', array('class'=>'percentage'));

            // number of user
            $usernumbercell = clone($contentcelldefault);
            $usernumbercell->text = $usernumber;
            $rows['usernumber'][] = $usernumbercell;

            // percentage of user
            $numbercell = clone($contentcelldefault);
            $numbercell->text = $userpercentage;
            $rows['userpercentage'][] = $numbercell;
        }

        $table->head = $rows['header'];
        $trgraph = new html_table_row($rows['graph']);
        $trusernumber = new html_table_row($rows['usernumber']);
        $truserpercentage = new html_table_row($rows['userpercentage']);
        $table->data = array($trgraph, $trusernumber, $truserpercentage);

        $header = html_writer::tag('h3',format_string(get_string("responses", "smartchoice")));
        $html .= html_writer::tag('div', $header, array('class'=>'responseheader'));
        $html .= html_writer::tag('div', html_writer::table($table), array('class'=>'response'));

        return $html;
    }
}
