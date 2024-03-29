<?php
/**
 * @filesource modules/booking/models/email.php
 *
 * @copyright 2016 Goragod.com
 * @license http://www.kotchasan.com/license/
 *
 * @see http://www.kotchasan.com/
 */

namespace Booking\Email;

use Kotchasan\Date;
use Kotchasan\Language;

/**
 * ส่งอีเมลไปยังผู้ที่เกี่ยวข้อง.
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\KBase
{
    /**
     * ส่งอีเมลแจ้งการทำรายการ
     *
     * @param string $mailto อีเมล
     * @param string $name   ชื่อ
     * @param array  $order ข้อมูล
     */
    public static function send($mailto, $name, $order)
    {
        if (self::$cfg->noreply_email != '') {
            // ข้อความ
            $msg = array(
                '{LNG_Book a meeting}',
                '{LNG_Contact name}: '.$name,
                '{LNG_Topic}: '.$order['topic'],
                '{LNG_Date}: '.Date::format($order['begin']).' - '.Date::format($order['end']),
                '{LNG_Status}: '.Language::find('BOOKING_STATUS', '', $order['status']),
                'URL: '.WEB_URL,
            );
            $msg = Language::trans(implode("\n", $msg));
            // ส่งอีเมลไปยังผู้ทำรายการเสมอ
            $emails = array($mailto => $mailto.'<'.$name.'>');
            // อีเมลของมาชิกที่สามารถอนุมัติได้ทั้งหมด
            $query = \Kotchasan\Model::createQuery()
                ->select('username', 'name')
                ->from('user')
                ->where(array(
                    array('social', 0),
                    array('active', 1),
                ))
                ->andWhere(array(
                    array('status', 1),
                    array('permission', 'LIKE', '%,can_approve_room,%'),
                ), 'OR')
                ->cacheOn();
            foreach ($query->execute() as $item) {
                $emails[$item->username] = $item->username.'<'.$item->name.'>';
            }
            // ส่งอีเมล
            $subject = '['.self::$cfg->web_title.'] '.Language::get('Book a meeting');
            $err = \Kotchasan\Email::send(implode(',', $emails), self::$cfg->noreply_email, $subject, nl2br($msg));
            if ($err->error()) {
                // คืนค่า error
                return strip_tags($err->getErrorMessage());
            } else {
                // คืนค่า
                return Language::get('Your message was sent successfully');
            }
        } else {
            // ไม่สามารถส่งอีเมล์ได้
            return Language::get('Saved successfully');
        }
    }
}
