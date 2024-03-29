<?php
/**
 * @filesource modules/booking/models/report.php
 *
 * @copyright 2016 Goragod.com
 * @license http://www.kotchasan.com/license/
 *
 * @see http://www.kotchasan.com/
 */

namespace Booking\Report;

use Gcms\Login;
use Kotchasan\Database\Sql;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * โมเดลสำหรับ (report.php).
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * Query ข้อมูลสำหรับส่งให้กับ DataTable.
     *
     * @param array $index
     *
     * @return \Kotchasan\Database\QueryBuilder
     */
    public static function toDataTable($index)
    {
        $where = array(
            array('V.status', $index['status']),
        );
        if ($index['room_id'] > 0) {
            $where[] = array('V.room_id', $index['room_id']);
        }
        $sql = Sql::create('(CASE WHEN NOW() BETWEEN V.`begin` AND V.`end` THEN 1 WHEN NOW() > V.`end` THEN 2 ELSE 0 END) AS `today`');

        return static::createQuery()
            ->select('V.id', 'V.topic', 'V.room_id', 'R.name', 'U.name contact', 'U.phone', 'V.begin', 'V.end', 'V.create_date', 'V.reason', $sql)
            ->from('reservation V')
            ->join('rooms R', 'INNER', array('R.id', 'V.room_id'))
            ->join('user U', 'LEFT', array('U.id', 'V.member_id'))
            ->where($where);
    }

    /**
     * รับค่าจาก action.
     *
     * @param Request $request
     */
    public function action(Request $request)
    {
        $ret = array();
        // session, referer, สามารถอนุมัติได้
        if ($request->initSession() && $request->isReferer() && $login = Login::isMember()) {
            if (Login::notDemoMode($login) && Login::checkPermission($login, 'can_approve_room')) {
                // รับค่าจากการ POST
                $action = $request->post('action')->toString();
                // id ที่ส่งมา
                if (preg_match_all('/,?([0-9]+),?/', $request->post('id')->toString(), $match)) {
                    if ($action === 'delete') {
                        $query = static::createQuery()
                            ->select('id')
                            ->from('reservation')
                            ->where(array(
                                array('id', $match[1]),
                                Sql::create('NOW() < `begin`'),
                            ));
                        $ids = array();
                        foreach ($query->execute() as $item) {
                            $ids[] = $item->id;
                        }
                        if (!empty($ids)) {
                            // ลบ
                            $this->db()->delete($this->getTableName('reservation'), array('id', $ids), 0);
                            $this->db()->delete($this->getTableName('reservation_data'), array('reservation_id', $ids), 0);
                        }
                        // reload
                        $ret['location'] = 'reload';
                    }
                }
            }
        }
        if (empty($ret)) {
            $ret['alert'] = Language::get('Unable to complete the transaction');
        }
        // คืนค่า JSON
        echo json_encode($ret);
    }
}
