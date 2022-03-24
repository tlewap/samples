<?php

namespace App\Command;

use App\Controller\APIController;
use App\Entity\Check;
use App\Entity\Product;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\Response;

class CheckProducts extends Command
{
    protected static $defaultName = "TI:cron";

    private $container;
    private $api;

    private array $products;

    public function __construct(ContainerInterface $container, APIController $api)
    {
        parent::__construct();
        $this->container = $container;
        $this->api = $api;
        $this->products = $this->container->get('doctrine')->getManager()
            ->getRepository(Product::class)
            ->findAll();
        $this->checkAllrepository();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        return Command::SUCCESS;

    }

    protected function checkAllrepository()
    {
        $products = $this->products;
        array_map(fn($product) => $this->checkProduct($product), $products);
    }

    protected function checkProduct(Product $product)
    {
        $check = $this->container->get('doctrine')->getManager()
            ->getRepository(Check::class)
            ->findOneBy(array('product' => $product->getId()));

        if ($check && $check->getId()) {
            $check->setProduct($product);
        } else {
            $check = new Check();
        }

        $check->setProduct($product);
        $check->setLastCheck(new DateTime('NOW'));

        echo $product->getName() . " ";
        if ($product->isNeeded()) {
            echo 'need more ';

            $stock_from_api = $this->updateStockFromApi($product);

            echo 'reserved ' . $stock_from_api . ' by API';
            $product->addQty($stock_from_api);
            $this->container->get('doctrine')->getManager()->persist($product);
        }



        $this->container->get('doctrine')->getManager()->persist($check);
        $this->container->get('doctrine')->getManager()->flush();
        echo PHP_EOL;
    }


    protected function getStockFromApi($product)
    {
        //TODO get real quantity from api
        $response = $this->api->connect();
        return rand(0, $response);
    }


}
