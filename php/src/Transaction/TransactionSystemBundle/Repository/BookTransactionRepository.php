<?php
namespace Transaction\TransactionSystemBundle\Repository;

use Doctrine\ORM\EntityRepository;

class BookTransactionRepository extends EntityRepository
{  
    public function checkBuyerWithoutCreditPendingTransaction($data) {
        $qb = $this->createQueryBuilder('c');
        $qb->select('c')
                ->where('c.status=:status AND c.buyerId=:buyerId AND c.sellerId=:sellerId AND c.withCredit=:withCredit')
                ->setParameter('status', $data['status'])
                ->setParameter('buyerId', $data['buyer_id'])
                ->setParameter('sellerId', $data['seller_id'])
                ->setParameter('withCredit', $data['with_credit']);
        $query = $qb->getQuery();
        $response = $query->getResult();
        return $response;
    }
    
    public function checkBuyerPendingTransaction($data) {
        $qb = $this->createQueryBuilder('c');
        $qb->select('c')
                ->where('c.status=:status AND c.buyerId=:buyerId')
                ->setParameter('status', $data['status'])
                ->setParameter('buyerId', $data['buyer_id']);
        $query = $qb->getQuery();
        $response = $query->getResult();
        return $response;
    }

    public function getInitTransactions($data) {
        $qb = $this->createQueryBuilder('c');
        $qb->select('c')
                ->where('c.status=:status AND c.sellerId=:sellerId')
                ->setParameter('status', $data['status'])
                ->setParameter('sellerId', $data['seller_id']);
        $query = $qb->getQuery();
        $response = $query->getResult();
        return $response;
    }

    public function cancleBooking($data) {
        $query = $this->createQueryBuilder('u')
                ->update()
                ->set('u.status', '?1')
                ->set('u.timeUpdateStatusH', '?2')
                ->set('u.timeUpdateStatus', '?3')
                ->where('u.id=?4')
                ->setParameter(1, $data['status'])
                ->setParameter(2, $data['time_update_status_h'])
                ->setParameter(3, $data['time_update_status'])
                ->setParameter(4, $data['booking_id'])
                ->getQuery();
        $reponse = $query->getResult();

        if($reponse){
                return true;
        } else {
            return false;
        }
    }

    public function updateBookingOnProcessTransaction($data) {
        $query = $this->createQueryBuilder('u')
                ->update()
                ->set('u.status', '?1')
                ->set('u.timeUpdateStatusH', '?2')
                ->set('u.timeUpdateStatus', '?3')
                ->set('u.transactionId', '?4')
                ->where('u.id=?5')
                ->setParameter(1, $data['status'])
                ->setParameter(2, $data['time_update_status_h'])
                ->setParameter(3, $data['time_update_status'])
                ->setParameter(4, $data['transaction_id'])
                ->setParameter(5, $data['booking_id'])
                ->getQuery();
        $reponse = $query->getResult();

        if($reponse){
                return true;
        } else {
            return false;
        }
    }
   
   /*
     * Get transaction history
     * @param $reqObj
     */
    public function getBusinessTransactionHistory($data) {
        $qb = $this->createQueryBuilder('c');
        $qb->select("c")
                ->where('c.sellerId=:sellerId')
                ->setFirstResult($data['skip'])
                ->setMaxResults($data['limit'])
                ->setParameter('sellerId', $data['seller_id'])
                ->orderBy('c.timeInitH', 'DESC');
        $query = $qb->getQuery();
        $response = $query->getResult();
        return $response;
    }
    
    /*
     * Get total transaction history
     * @param $reqObj
     */
    public function getTotalBusinessTransactionHistory($data) {
        $qb = $this->createQueryBuilder('c');
        $qb->select("COUNT(c.id) AS totalRecords")
                ->where('c.sellerId=:sellerId')
                ->setParameter('sellerId', $data['seller_id']);
        $query = $qb->getQuery();
        $response = $query->getResult();
        
        if(!empty($response)) {
            if(($data['limit'] + $data['skip']) >= $response[0]['totalRecords']) {
                $response[0]['hasNext'] = false;
            } else {
                $response[0]['hasNext'] = true;
            }
            return $response[0];
        }
    }
}