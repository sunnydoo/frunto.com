Page({
  data: {

    headerItems: ["轻度", "中度", "重度"],
    rowsOfKnows: [
      { "name": "粪便虫卵指数", "mild": " <100", "moderate": "100 - 500", "severe": ">500"},
      { "name": "粪便虫卵指数", "mild": " <100", "moderate": "100 - 500", "severe": ">500"},
      { "name": "粪便虫卵指数", "mild": " <100", "moderate": "100 - 500", "severe": ">500" },
      { "name": "粪便虫卵指数", "mild": " <100", "moderate": "100 - 500", "severe": ">500" },
      { "name": "粪便虫卵指数", "mild": " <100", "moderate": "100 - 500", "severe": ">500" },
      { "name": "粪便虫卵指数", "mild": " <100", "moderate": "100 - 500", "severe": ">500" },
      { "name": "粪便虫卵指数", "mild": " <100", "moderate": "100 - 500", "severe": ">500" },
    ],

    indicatorDots: true,
    vertical: false,
    autoplay: false,
    interval: 2000,
    duration: 500
  },
  changeIndicatorDots: function (e) {
    this.setData({
      indicatorDots: !this.data.indicatorDots
    })
  },
  changeAutoplay: function (e) {
    this.setData({
      autoplay: !this.data.autoplay
    })
  },
  intervalChange: function (e) {
    this.setData({
      interval: e.detail.value
    })
  },
  durationChange: function (e) {
    this.setData({
      duration: e.detail.value
    })
  }
})
