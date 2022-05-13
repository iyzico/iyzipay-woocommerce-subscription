<?php
require_once 'header.php';
require_once 'sidebar.php'

?>




<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <!-- Content Header (Page header) -->

  <section class="content">

    <div class="box">
      <div class="box-header with-border">
        <h3 class="box-title">Günlük Cari Rapor </h3>

        <div class="box-tools pull-right">
          <button type="button" class="btn btn-box-tool" data-widget="collapse" data-toggle="tooltip" title="" data-original-title="Collapse">
            <i class="fa fa-minus"></i></button>
            <button type="button" class="btn btn-box-tool" data-widget="remove" data-toggle="tooltip" title="" data-original-title="Remove">
              <i class="fa fa-times"></i></button>
            </div>
          </div>
          <div class="box-body">
            <div class="row">



              <!-- ./col -->
              <div class="col-lg-3 col-xs-6">
                <!-- small box -->
                <div class="small-box bg-red">
                  <div class="inner">
                    <h3> <?php
                    $sql=$db->qSQL("SELECT SUM(products_price) as sales_total FROM sales INNER JOIN products ON products.products_id=sales.products_id WHERE DAY(sales_date)=DAY(CURDATE())");
                    $sales_total=$sql->fetch(PDO::FETCH_ASSOC);
                    echo number_format($sales_total=$sales_total['sales_total']);
                    ?> ₺</h3>

                    <p>Toplam Satış</p>
                  </div>
                  <div class="icon">
                    <i class="ion ion-pie-graph"></i>
                  </div>
                </div>
              </div>
              <!-- ./col -->


              <!-- ./col -->
              <div class="col-lg-3 col-xs-6">
                <!-- small box -->
                <div class="small-box bg-green">
                  <div class="inner">
                    <h3>  <?php
                      $sql=$db->qSQL("SELECT SUM(operation_price) as revenue FROM operation  WHERE operation.operation_type='Gelir' AND DAY(operation_date)=DAY(CURDATE()) ");
                       $revenue=$sql->fetch(PDO::FETCH_ASSOC);
                       echo number_format($revenue=$revenue['revenue']);
                      ?> ₺</h3>

                    <p>Toplam Gelir (Tahsilat)</p>
                  </div>
                  <div class="icon">
                    <i class="ion ion-pie-graph"></i>
                  </div>
                </div>
              </div>
              <!-- ./col -->

               <!-- ./col -->
              <div class="col-lg-3 col-xs-6">
                <!-- small box -->
                <div class="small-box bg-orange">
                  <div class="inner">
                    <h3>  <?php
                      $sql=$db->qSQL("SELECT SUM(operation_price) as revenue FROM operation  WHERE operation.operation_type='Gider' AND DAY(operation_date)=DAY(CURDATE()) ");
                       $revenue=$sql->fetch(PDO::FETCH_ASSOC);
                       echo number_format($revenue=$revenue['revenue']);
                      ?> ₺</h3>

                    <p>Toplam Gider</p>
                  </div>
                  <div class="icon">
                    <i class="ion ion-pie-graph"></i>
                  </div>
                </div>
              </div>
              <!-- ./col -->

                  <!-- ./col -->
              <div class="col-lg-3 col-xs-6">
                <!-- small box -->
                <div class="small-box bg-blue">
                  <div class="inner">
                    <h3>  <?php
                      $sql=$db->qSQL("
                        SELECT SUM(
                        CASE WHEN operation_type='Gelir' THEN operation_price ELSE 0 END
                        )-SUM(
                        CASE WHEN operation_type='Gider' THEN operation_price ELSE 0 END
                        ) as safe FROM operation WHERE DAY(operation_date)=DAY(CURDATE())
                        ");
                       $rows=$sql->fetch(PDO::FETCH_ASSOC);
                       echo number_format($rows['safe']);
                      ?> ₺</h3>

                    <p>Kasa</p>
                  </div>
                  <div class="icon">
                    <i class="ion ion-pie-graph"></i>
                  </div>
                </div>
              </div>
              <!-- ./col -->




            </div>
          </div>
          <!-- /.box-body -->
       <!--  <div class="box-footer">
          Footer
        </div> -->
        <!-- /.box-footer-->
      </div>
      <!-- /.box -->

    <!-- Default box -->
    <div class="box">
      <div class="box-header with-border">
        <h3 class="box-title">Genel Cari Rapor </h3>

        <div class="box-tools pull-right">
          <button type="button" class="btn btn-box-tool" data-widget="collapse" data-toggle="tooltip" title="" data-original-title="Collapse">
            <i class="fa fa-minus"></i></button>
            <button type="button" class="btn btn-box-tool" data-widget="remove" data-toggle="tooltip" title="" data-original-title="Remove">
              <i class="fa fa-times"></i></button>
            </div>
          </div>
          <div class="box-body">
            <div class="row">



              <!-- ./col -->
              <div class="col-lg-3 col-xs-6">
                <!-- small box -->
                <div class="small-box bg-red">
                  <div class="inner">
                    <h3> <?php
                    $sql=$db->qSQL("SELECT SUM(products_price) as sales_total FROM sales INNER JOIN products ON products.products_id=sales.products_id");
                    $sales_total=$sql->fetch(PDO::FETCH_ASSOC);
                    echo number_format($sales_total=$sales_total['sales_total']);
                    ?> ₺</h3>

                    <p>Toplam Satış</p>
                  </div>
                  <div class="icon">
                    <i class="ion ion-pie-graph"></i>
                  </div>
                </div>
              </div>
              <!-- ./col -->


              <!-- ./col -->
              <div class="col-lg-3 col-xs-6">
                <!-- small box -->
                <div class="small-box bg-green">
                  <div class="inner">
                    <h3>  <?php
                      $sql=$db->qSQL("SELECT SUM(operation_price) as revenue FROM operation  WHERE operation.operation_type='Gelir' ");
                       $revenue=$sql->fetch(PDO::FETCH_ASSOC);
                       echo number_format($revenue=$revenue['revenue']);
                      ?> ₺</h3>

                    <p>Toplam Gelir (Tahsilat)</p>
                  </div>
                  <div class="icon">
                    <i class="ion ion-pie-graph"></i>
                  </div>
                </div>
              </div>
              <!-- ./col -->

               <!-- ./col -->
              <div class="col-lg-3 col-xs-6">
                <!-- small box -->
                <div class="small-box bg-orange">
                  <div class="inner">
                    <h3>  <?php
                      $sql=$db->qSQL("SELECT SUM(operation_price) as revenue FROM operation  WHERE operation.operation_type='Gider' ");
                       $revenue=$sql->fetch(PDO::FETCH_ASSOC);
                       echo number_format($revenue=$revenue['revenue']);
                      ?> ₺</h3>

                    <p>Toplam Gider</p>
                  </div>
                  <div class="icon">
                    <i class="ion ion-pie-graph"></i>
                  </div>
                </div>
              </div>
              <!-- ./col -->

                  <!-- ./col -->
              <div class="col-lg-3 col-xs-6">
                <!-- small box -->
                <div class="small-box bg-blue">
                  <div class="inner">
                    <h3>  <?php
                      $sql=$db->qSQL("
                        SELECT SUM(
                        CASE WHEN operation_type='Gelir' THEN operation_price ELSE 0 END
                        )-SUM(
                        CASE WHEN operation_type='Gider' THEN operation_price ELSE 0 END
                        ) as safe FROM operation
                        ");
                       $rows=$sql->fetch(PDO::FETCH_ASSOC);
                       echo number_format($rows['safe']);
                      ?> ₺</h3>

                    <p>Kasa</p>
                  </div>
                  <div class="icon">
                    <i class="ion ion-pie-graph"></i>
                  </div>
                </div>
              </div>
              <!-- ./col -->




            </div>
          </div>
          <!-- /.box-body -->
       <!--  <div class="box-footer">
          Footer
        </div> -->
        <!-- /.box-footer-->
      </div>
      <!-- /.box -->

    </section>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->

  <?php require_once 'footer.php'; ?>
