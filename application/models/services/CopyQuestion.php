<?php


namespace LimeSurvey\Models\Services;

use LimeSurvey\Datavalueobjects\CopyQuestionValues;

/**
 * Class CopyQuestion
 *
 * This class is responsible for the copy question process.
 *
 * @package LimeSurvey\Models\Services
 */
class CopyQuestion
{

    /**
     * @var CopyQuestionValues values needed to copy a question
     */
    private $copyQuestionValues;

    /**
     * @var \Question the new question
     */
    private $newQuestion;

    /**
     * CopyQuestion constructor.
     *
     * @param CopyQuestionValues $copyQuestionValues
     */
    public function __construct($copyQuestionValues)
    {
        $this->copyQuestionValues = $copyQuestionValues;
        $this->newQuestion = null;
    }

    /**
     * Copies the question and all necessary values/parameters
     * (languages, subquestions, answeroptions, defaultanswers, settings)
     *
     * @param array $copyOptions has the following boolean elements
     *                          ['copySubquestions']
     *                          ['copyAnswerOptions']
     *                          ['copyDefaultAnswers']
     *                          ['copySettings'] --> generalSettings and advancedSettings
     *
     * @return true if new copied question could be saved, false otherwise
     */
    public function copyQuestion($copyOptions)
    {
        $copySuccessful = $this->createNewCopiedQuestion(
            $this->copyQuestionValues->getQuestionCode(),
            $this->copyQuestionValues->getQuestionGroupId(),
            $this->copyQuestionValues->getQuestiontoCopy()
        );
        if ($copySuccessful) {
            //copy question languages
            $this->copyQuestionLanguages($this->newQuestion);

            //copy subquestions
            if ($copyOptions['copySubquestions']) {
                $this->copyQuestionsSubQuestions($this->copyQuestionValues->getQuestiontoCopy()->qid);
            }

            //copy answer options
            if ($copyOptions['copyAnswerOptions']) {
                $this->copyQuestionsAnswerOptions($this->copyQuestionValues->getQuestiontoCopy()->qid);
            }

            //copy default answers
            if ($copyOptions['copyDefaultAnswers']) {
                $this->copyQuestionsDefaultAnswers($this->copyQuestionValues->getQuestiontoCopy()->qid);
            }
        }
        return $copySuccessful;
    }


    /**
     * Creates a new question copying the values from questionToCopy
     *
     * @param string $questionCode
     * @param int $groupId
     * @param \Question $questionToCopy the question that should be copied
     *
     * @return bool true if question could be saved, false otherwise
     */
    public function createNewCopiedQuestion($questionCode, $groupId, $questionToCopy)
    {
        $this->newQuestion = new \Question();
        $this->newQuestion->attributes = $questionToCopy->attributes;
        $this->newQuestion->title = $questionCode;
        $this->newQuestion->gid = $groupId;
        $this->newQuestion->qid = null;

        return $this->newQuestion->save();
    }

    /**
     * Copies the languages of a question.
     *
     * @param \Question $oQuestion
     *
     * @return bool true if all languages could be copied,
     *              false if no language was copied or save failed for one language
     */
    private function copyQuestionLanguages($oQuestion)
    {
        $allLanguagesAreCopied = false;
        if ($oQuestion !== null) {
            $allLanguagesAreCopied = true;
            foreach ($oQuestion->questionl10ns as $sLanguage) {
                $copyLanguage = new \QuestionL10n();
                $copyLanguage->attributes = $sLanguage->attributes;
                $copyLanguage->id = null; //new id needed
                $allLanguagesAreCopied = $allLanguagesAreCopied && $copyLanguage->save();
            }
        }

        return $allLanguagesAreCopied;
    }

    /**
     * Copy subquestions of a question
     *
     * @param int $parentId id of question to be copied
     *
     * @return bool true if all subquestions could be copied&saved, false if a subquestion could not be saved
     */
    private function copyQuestionsSubQuestions($parentId)
    {
        //copy subquestions
        $areSubquestionsCopied = true;
        $subquestions = \Question::model()->findAllByAttributes(['parent_qid' => $parentId]);

        foreach ($subquestions as $subquestion) {
            $copiedSubquestion = new \Question();
            $copiedSubquestion->attributes = $subquestion->attributes;
            $copiedSubquestion->parent_qid = $this->newQuestion->sid;
            $copiedSubquestion->qid = null; //new question id needed ...
            $areSubquestionsCopied = $areSubquestionsCopied && $copiedSubquestion->save();
        }

        return $areSubquestionsCopied;
    }

    /**
     * Copies the answer options of a question
     *
     * @param int $questionIdToCopy
     */
    private function copyQuestionsAnswerOptions($questionIdToCopy)
    {
        $answerOptions = \Answer::model()->findAllByAttributes(['qid' => $questionIdToCopy]);
        foreach ($answerOptions as $answerOption) {
            $copiedAnswerOption = new \Answer();
            $copiedAnswerOption->attributes = $answerOption->attributes;
            $copiedAnswerOption->aid = null;
            if ($copiedAnswerOption->save()) {
                //copy the languages
                foreach ($answerOption->answerl10ns as $answerLanguage) {
                    $copiedAnswerOptionLanguage = new \AnswerL10n();
                    $copiedAnswerOptionLanguage->attributes = $answerLanguage->attributes;
                    $copiedAnswerOptionLanguage->id = null;
                    $copiedAnswerOptionLanguage->aid = $copiedAnswerOption->aid;
                    $copiedAnswerOptionLanguage->save();
                }
            }
        }
    }

    /**
     * Copies the default answers of the question
     *
     * @param int $questionIdToCopy
     */
    private function copyQuestionsDefaultAnswers($questionIdToCopy){
        $defaultAnswers = \DefaultValue::model()->findAllByAttributes(['qid' => $questionIdToCopy]);
        foreach ($defaultAnswers as $defaultAnswer) {
            $copiedDefaultAnswer = new \DefaultValue();
            $copiedDefaultAnswer->attributes = $defaultAnswer->attributes;
            $copiedDefaultAnswer->qid = $this->newQuestion->qid;
            $copiedDefaultAnswer->dvid = null;
            if ($copiedDefaultAnswer->save()) {
                //copy languages if needed
                foreach ($copiedDefaultAnswer->defaultvalueL10ns as $defaultAnswerL10n) {
                    $copieDefaultAnswerLanguage = new \DefaultValueL10n();
                    $copieDefaultAnswerLanguage->attributes = $defaultAnswerL10n->attributes;
                    $copieDefaultAnswerLanguage->dvid = $copiedDefaultAnswer->dvid;
                    $copieDefaultAnswerLanguage->id = null;
                    $copieDefaultAnswerLanguage->save();
                }
            }
        }
    }

    /**
     * Returns the new created question or null if question was not copied.
     *
     * @return \Question|null
     */
    public function getNewCopiedQuestion()
    {
        return $this->newQuestion;
    }
}