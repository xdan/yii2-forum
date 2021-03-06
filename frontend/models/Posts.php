<?php

namespace worstinme\forum\frontend\models;

use Yii;

/**
 * This is the model class for table "forum_posts".
 *
 * @property integer $id
 * @property integer $thread_id
 * @property string $name
 * @property string $content
 * @property integer $state
 * @property integer $created_at
 * @property integer $updated_at
 * @property integer $user_id
 */
class Posts extends \yii\db\ActiveRecord
{
    const STATE_ACTIVE = 1;
    const STATE_HIDDEN = 0;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'forum_posts';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['content'], 'required'],
            [['thread_id', 'state', 'created_at', 'updated_at', 'user_id'], 'integer'],
            [['content'], 'string'],
            [['name'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('forum', 'ID'),
            'thread_id' => Yii::t('forum', 'Thread ID'),
            'name' => Yii::t('forum', 'Name'),
            'content' => Yii::t('forum', 'Content'),
            'state' => Yii::t('forum', 'State'),
            'created_at' => Yii::t('forum', 'Created At'),
            'updated_at' => Yii::t('forum', 'Updated At'),
            'user_id' => Yii::t('forum', 'User ID'),
        ];
    }

    public function behaviors()
    {
        return [
            \yii\behaviors\TimestampBehavior::className(),
        ];
    }

    public function getUser()
    {
        return $this->hasOne(Yii::$app->controller->module->profileModel, [Yii::$app->controller->module->profileModelUserColumn => 'user_id']);
    }

    public function getUserAvatar() {
        return !empty($this->user) ? $this->user->{Yii::$app->controller->module->profileAvatarAttribute} : null;
    }

    public function getThread()
    {
        return $this->hasOne(Threads::className(), ['id' => 'thread_id'])->inverseOf('posts');
    }

    public function beforeSave($insert) {
        if (parent::beforeSave($insert)) {

            if ($insert) {
                $this->user_id = Yii::$app->user->identity->id;
                $this->state = $this::STATE_ACTIVE;
            }
            
            return true;
        }
        else return false;
    }

    public function getCanEdit() {
        if (Yii::$app->user->isGuest) {
            return false;
        }
        elseif (Yii::$app->user->can('admin') || Yii::$app->user->can('moder')) {
            return true;
        }
        elseif(Yii::$app->user->identity->id == $this->user_id && ($this->created_at + Yii::$app->controller->module->postEditDelay) >= time()) {
            return true;
        }
        return false;
    }

    public function getCanDelete() {
        if (Yii::$app->user->isGuest) {
            return false;
        }
        elseif (Yii::$app->user->can('admin') || Yii::$app->user->can('moder')) {
            return true;
        }
        elseif(Yii::$app->user->identity->id == $this->user_id && ($this->created_at + Yii::$app->controller->module->postDeleteDelay) >= time()) {
            return true;
        }
        return false;
    }

    public function getEditUrl() {
        return ['/forum/threads/reply','thread_id'=>$this->thread->id,'lang'=>$this->thread->forum->lang,'post_id'=>$this->id];
    }

    public function getDeleteUrl() {
        return ['/forum/threads/post-delete','post_id'=>$this->id,'lang'=>$this->thread->forum->lang];
    }
}
