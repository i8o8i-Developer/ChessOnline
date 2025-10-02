# ChessEnginePyProvidesLegalMoveValidationAndGameHelpers
import chess
import chess.pgn

class ChessEngine:
    # CreateNewBoardReturnsStartingBoardFen
    @staticmethod
    def CreateNewBoard():
        Board = chess.Board()
        return Board.fen()

    # GetLegalMovesForFenReturnsListOfSanMoves
    @staticmethod
    def GetLegalMovesForFen(Fen):
        Board = chess.Board(Fen)
        LegalMoves = []
        for Move in Board.legal_moves:
            LegalMoves.append(Board.san(Move))
        return LegalMoves

    # IsMoveLegalAppliesMoveIfLegalReturnsNewFenOrRaises
    @staticmethod
    def IsMoveLegalAndApply(Fen, UciMove):
        Board = chess.Board(Fen)
        Move = None
        try:
            Move = chess.Move.from_uci(UciMove)
            if Move not in Board.legal_moves:
                raise ValueError("IllegalMove")
            
            # GetSanBeforePushingTheMove
            San = Board.san(Move)
            Board.push(Move)
            
            return {
                "Fen": Board.fen(),
                "San": San,
                "IsCheckmate": Board.is_checkmate(),  # AddExplicitCheckmateFlag
                "IsStalemate": Board.is_stalemate()
            }
        except chess.InvalidMoveError:
            raise ValueError("InvalidMoveFormat")
        except Exception as e:
            raise ValueError(str(e))

    # GetWinnerFromBoardReturnsResultString
    @staticmethod
    def GetResultFromFen(Fen):
        Board = chess.Board(Fen)
        if Board.is_checkmate():
            # CheckWhoToMoveIsLoser
            ToMove = Board.turn
            # IfToMoveIsTrueThenWhiteToMoveAndWhiteLost
            Winner = "Black" if Board.turn else "White"
            return Winner
        if Board.is_stalemate() or Board.is_insufficient_material():
            return "Draw"
        return "Ongoing"
    