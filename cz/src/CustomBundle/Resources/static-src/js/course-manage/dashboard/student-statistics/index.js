class StudentsStudyAnalysis {
  constructor() {
    this.multiAnalysisDatas = $("input[name='multi-analysis']").val();
    this.searchUrl = $("input[name='search-url']").val();
    this.searchCondition = {
      'searchCondition': $("input[name='search-condition']").val(),
      'orderBy': '',
      'isDesc': '',
    };
  }

  init() {
    this.initStudentsMultiAnalysis();
    this.initStudentsAnalysis();
    this.bindSortEvent();
    this.bindPaginatorEvent();
    this.bindSearchEvent();
  }

  initStudentsMultiAnalysis() {
    const myChart = echarts.init(document.getElementById('js-student-learn-chart'));

    let option = {
      title: {
        text: '本班学生多维分析',
        left: 'center',
        padding: [20,0,0,0],
        textStyle: {
          color: '#313131',
          fontFamily: 'PingFangSC-Semibold',
          fontSize: 16
        }
      },
      tooltip:{},
      backgroundColor: '#f7f7f7',
      radar: {
        center: ['50%', '55%'],
        radius: '56%',
        indicator: [
          { name: '出勤率', max: 100},
          { name: '课堂积极性', max: 100},
          { name: '课外积极性', max: 100},
          { name: '平时成绩', max: 100}
        ]
      },
      series: [{
        name: '班级学生多维分析',
        type: 'radar',
        data : [
          {
            value : this.multiAnalysisDatas.split(',')
          }
        ]
      }]
    };

    myChart.setOption(option);
  }

  initStudentsAnalysis() {
    this.fetchDatas(this.searchUrl, this.searchCondition);
  }

  bindPaginatorEvent() {
    $('#js-student-learn-datas').on('click', '.pagination li', (e) => {
      let self = e.currentTarget;
      let url = $(self).data('url');

      this.fetchDatas(url, this.searchCondition);
    });
  }

  bindSortEvent() {
    $('#js-student-learn-datas').on('click', '.js-sort-conditions', (e) => {
      let self = e.currentTarget;

      this.searchCondition.orderBy = $(self).data('condition');
      this.searchCondition.isDesc = this.searchCondition.isDesc == 'true' ? 'false' : 'true';
      let url = $("input[name='page-url']").val();

      this.fetchDatas(url, this.searchCondition);

      e.stopPropagation();
    });
  }

  bindSearchEvent() {
    $('.search-submit').on('click', (e) => {
      this.searchCondition.searchCondition = $("input[name='search-condition']").val();

      this.fetchDatas(this.searchUrl, this.searchCondition);
    });
  }

  fetchDatas(url, condition) {
    $.get(url, condition, (response) => {
      $('#js-student-learn-datas').html(response);
    });
  }
}

let studentsStudyAnalysis = new StudentsStudyAnalysis();
studentsStudyAnalysis.init();