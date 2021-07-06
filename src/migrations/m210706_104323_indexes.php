<?php

use yii\db\Migration;

/**
 * Class m210615_063719_alfapay_invoice
 */
class m210706_104323_indexes extends Migration
{
    /**
     * @inheritdoc
     */
    public function up()
    {
        $this->createIndex('idx_object', 'sberpay_invoice', ['related_id', 'related_model']);
        $this->createIndex('idx_orderid', 'sberpay_invoice', ['orderId']);
    }

    /**
     * @inheritdoc
     */
    public function down()
    {
        $this->createIndex('idx_object', 'sberpay_invoice');
        $this->createIndex('idx_orderid', 'sberpay_invoice');
    }
}
