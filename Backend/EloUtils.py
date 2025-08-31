# EloUtilsPy ProvidesEloCalculationHelpers
import math

class EloUtils:
    # CalculateExpectedScore ReturnsExpectedScoreForPlayerA
    @staticmethod
    def CalculateExpectedScore(RatingA, RatingB):
        Qa = 10 ** (RatingA / 400.0)
        Qb = 10 ** (RatingB / 400.0)
        ExpectedA = Qa / (Qa + Qb)
        return ExpectedA

    # UpdateElo ReturnsNewRatings
    @staticmethod
    def UpdateElo(RatingA, RatingB, ScoreA, K=32):
        ExpectedA = EloUtils.CalculateExpectedScore(RatingA, RatingB)
        NewA = RatingA + int(round(K * (ScoreA - ExpectedA)))
        ScoreB = 1.0 - ScoreA
        ExpectedB = 1.0 - ExpectedA
        NewB = RatingB + int(round(K * (ScoreB - ExpectedB)))
        return NewA, NewB