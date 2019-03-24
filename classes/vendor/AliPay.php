<?php
/**
 * Created by PhpStorm.
 * User: yangyang
 * Date: 2019/3/23
 * Time: 下午1:16
 */

namespace classes\vendor;

use classes\FirstClass;

class AliPay extends FirstClass
{
    public function __construct()
    {
        include dirname(__FILE__).'/alipay/AopSdk.php';
    }

    public function pay()
    {
        $aop = new \AopClient();
        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $aop->appId = '2019032063592746';
        $aop->rsaPrivateKey = 'MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQC9VduB70i0ctquIOYSA6hrY5sq7PYfb2H+Kr7BIqJ7LKUSe934XDiylA8uIaXzzv+yDtrC52IFHmXclqDjP7pCxA7a1j4B4KYETlSrmSYDC+AEu2FP2WIbSslqqYeFzLU4v+Ya60IMijzfu/5t/C4azRqPe2+utmOclpe4MTb715eOj9m9oFkUWKovzVYAEs11NRD9mzvfXy2FTeviswB6PX9l1qI9lyMguTr3UeNu6gwUpor9kPtbD1LGKjVqYbnVy3Sw+72ZLHCDS5Lr8IvsGy0p1BfNYpu7z4NmKL7grS9XKF3gncTdO7dbiqac3QHCVVF9aixoHLCkGbqSBV27AgMBAAECggEBAJE+YwT7bjfXgABihnw9SB8Rq0Azxd95j5/uZKU+j4yURBG46iAThv8fa8HE2Ez/d9G9aX5pCB3LIvMkrkmGYrnDM24XOjwwfMsC2BtHggyGOJUIte8OYR90sKzdXnR7BG6Db98oqGN8TJSJ3/W5phizTPPTCmrpyeKe98a6ImbwuwrAMV0vdwAHygZWgmWyhsKju+uW+Q2Pf+W1VsNBoW9V1SrWNamVhGjiL8POELpKDjf2HqR4OSZUzY94p+rFV5DREqPUnmvJ8Wm+4nMGR2BZoye1btQnrJWneDOadkj43kCY4D97jpb4WcHO3SIChIIiniX/h7wMBhTeNoG3UqECgYEA6i0h1bpAQE5U0QnFNsu37DgUY0h4hbJRe9b9VYkubVy8owabSDXSHAOyWbyOQtEPS6gtUUe5TgTvX48aMk/UL9HH/fqWFBMpSNzx9NsbYmT67mcdQt19NN1rTl+Aw5MHBCUMHhftJnX8FZiUSxR/TEGyzKoqNzQCckWbs51UfCsCgYEAzvruisfz4sfS4iKh9Rzv82LD4RLuQClFvUOe/ICSS0hcfxAZr5uCAcpLUWf9++oNXQ2p+ms+1nYt3Kf59jlRQNfJS6kA/LL6VaMex0kxNpc3cmGoZKvAokUu6POEX7hm55eRUzff5cZtrNIRLrat9MhPIYNNVKzr5pa6DgYcjLECgYAWN9X/+z3hXA08EBtACbAXn3J/wP5+YN7OSyWaGfvnsGbNaPEOttyKf+dKEFPHDyLOYNR747w3JMy5WlB3+Y7+Qn8hH8dhfDfsi9oBC1Db0wzj1bC3CWC5xVBLDVCgPbDqucEekdKUVZlnSyS2wukZCRq3xGVg+3RH90bAkerUJQKBgBsrj4LCSr6bUpCC0VXWzZzaezIhhQsBH/l/VfB5hDrDrfi0CVsnA8LuZkKybwHRsJ1lfMm1qFMIwm5z0nlC/uqPKQp4jfueFwSE3DZ24jHzWcD3Ft7YOPpUMJs91EOodkGygM42yHpmOOF11fGW43D9ln2kudMCvBjR8myC2t6RAoGAcrl82iu5bmSidn9FQxaKJe2+n4pcNpVy6Qdp7M7+/rmFTttZMX8JgUmkjWFsuZJfPVr9mJg32DB5Otpu18FgeS43nyeXSFeRbKLasZAPLHOJnLV/UBh2Q7H2SRo/Sv+hoM3flooSBcMw4UaLSbouRLyUMS0L517heB2Nvxd1PXs=';
        $aop->alipayrsaPublicKey='MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAg15qEtylhA8R8iIq+1XGX7agWBm+Pibj2XweLfIacLDFFAANq1kZoYPwdxi3bx0FGcRpl2XREqda6bAQEgLcEz4OL6AIIwoJs7IB53IDcoxe0a6ZaAZlmnPfkQqjwBU8EMYaVgwecQ0kUVH6Dt4E8/bVF1iIhUsm9/BKBd8Eo7aJYz0P0Qz09SS+rUuugTYRSGoAMb3d+sD/KT7DU33mpT5nf5iTSmxQdBnoLUABevsN2XsLddy5IhxPZzDCZsuBPICpdWMb764lNwZlfarscA8UvHtXI4OxXZHkle14igwU2jhNYPCoMloovvXwlf5g8Ez3sfm8bfBrtyEGq0RGSwIDAQAB';
        $aop->apiVersion = '1.0';
        $aop->postCharset='utf-8';
        $aop->format='json';
        $aop->signType='RSA2';
        $request = new \AlipayTradeWapPayRequest();
        $request->setBizContent("{" .
            "    \"body\":\"对一笔交易的具体描述信息。如果是多种商品，请将商品描述字符串累加传给body。\"," .
            "    \"subject\":\"大乐透\"," .
            "    \"out_trade_no\":\"70501111111S001111119\"," .
            "    \"timeout_express\":\"90m\"," .
            "    \"total_amount\":9.00," .
            "    \"product_code\":\"QUICK_WAP_WAY\"" .
            "  }");
        $result = $aop->pageExecute($request);
        echo $result;
    }
}