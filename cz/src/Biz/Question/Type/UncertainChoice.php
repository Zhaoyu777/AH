<?php

namespace Biz\Question\Type;

class UncertainChoice extends BaseQuestion implements TypeInterface
{
    public function create($fields)
    {
    }

    public function update($id, $fields)
    {
    }

    public function delete($id)
    {
    }

    public function get($id)
    {
    }

    public function judge($question, $answer)
    {
        if (count(array_diff($question['answer'], $answer)) == 0 && count(array_diff($answer, $question['answer'])) == 0) {
            return array('status' => 'right', 'score' => $question['score']);
        }

        if (count(array_diff($answer, $question['answer'])) == 0) {
            $percentage = intval(count($answer) / count($question['answer']) * 100);

            return array(
                'status' => 'partRight',
                'percentage' => $percentage,
                'score' => $question['missScore'],
            );
        }

        return array('status' => 'wrong', 'score' => 0);
    }

    public function filter(array $fields)
    {
        if (!empty($fields['choices'])) {
            $fields['metas'] = array('choices' => $fields['choices']);
        }

        return parent::filter($fields);
    }
}
